<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    private function captchaQuestion(Request $request): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $request->session()->put('password_reset_captcha_answer', (string) ($left + $right));

        return $left.' + '.$right;
    }

    /**
     * Display the password reset link request view.
     */
    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'captchaQuestion' => $this->captchaQuestion($request),
        ]);
    }

    /**
     * Handle a local password reset request without sending email.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'captcha_answer' => ['required', 'string'],
        ]);

        $expectedAnswer = (string) $request->session()->pull('password_reset_captcha_answer', '');
        if ($expectedAnswer === '' || trim((string) $validated['captcha_answer']) !== $expectedAnswer) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['captcha_answer' => 'Captcha tidak sesuai. Silakan coba lagi.']);
        }

        $user = DB::table('users')
            ->where('email', $validated['email'])
            ->first(['email', 'is_active']);

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email tidak terdaftar pada sistem.']);
        }

        if (! $user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun tidak aktif. Hubungi admin dinas.']);
        }

        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($plainToken),
                'created_at' => now(),
            ]
        );

        return redirect()->route('password.reset', [
            'token' => $plainToken,
            'email' => $validated['email'],
        ])->with('status', 'Verifikasi berhasil. Silakan buat password baru Anda.');
    }
}
