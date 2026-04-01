<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminCascadeDeleteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class CascadeDeleteController extends Controller
{
    public function __construct(
        private readonly AdminCascadeDeleteService $cascadeDeleteService
    ) {
    }

    public function index(): View
    {
        return view('admin.penghapusan.index', [
            'supportedEntities' => AdminCascadeDeleteService::supportedEntities(),
        ]);
    }

    public function destroy(Request $request, string $entity, int $id): RedirectResponse|JsonResponse
    {
        if (! $this->cascadeDeleteService->supports($entity)) {
            return $this->response($request, false, 'Tipe data tidak mendukung penghapusan menyeluruh.', 404);
        }

        try {
            $validated = $request->validate([
                'confirmation_key' => ['required', 'string', 'max:150'],
            ]);

            $expectedKey = $this->cascadeDeleteService->confirmationKey($entity, $id);
            if (! $expectedKey) {
                return $this->response($request, false, AdminCascadeDeleteService::entityLabel($entity).' tidak ditemukan.', 404);
            }

            if (mb_strtoupper(trim($validated['confirmation_key'])) !== $expectedKey) {
                throw ValidationException::withMessages([
                    'confirmation_key' => 'Kunci konfirmasi tidak sesuai. Ketik persis '.$expectedKey.'.',
                ]);
            }

            $result = $this->cascadeDeleteService->delete($entity, $id);

            return $this->response($request, true, $result['message']);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Validasi penghapusan menyeluruh gagal.';

            return $this->response($request, false, $message, 422);
        } catch (RuntimeException $exception) {
            return $this->response($request, false, $exception->getMessage(), 422);
        }
    }

    private function response(Request $request, bool $success, string $message, int $status = 200): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $success ? $status : max(400, $status));
        }

        return back()->with($success ? 'success' : 'error', $message);
    }
}
