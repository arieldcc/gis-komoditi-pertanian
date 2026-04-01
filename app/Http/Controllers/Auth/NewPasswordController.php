<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    private function captchaQuestion(Request $request): string
    {
        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $request->session()->put('password_reset_confirm_captcha_answer', (string) ($left + $right));

        return $left.' + '.$right;
    }

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'request' => $request,
            'captchaQuestion' => $this->captchaQuestion($request),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'captcha_answer' => ['required', 'string'],
        ]);

        $expectedAnswer = (string) $request->session()->pull('password_reset_confirm_captcha_answer', '');
        if ($expectedAnswer === '' || trim((string) $validated['captcha_answer']) !== $expectedAnswer) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'captcha_answer'))
                ->withErrors(['captcha_answer' => 'Captcha tidak sesuai. Silakan coba lagi.']);
        }

        $resetToken = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $resetToken || ! Hash::check($validated['token'], $resetToken->token)) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'captcha_answer'))
                ->withErrors(['email' => 'Permintaan reset password tidak valid atau sudah kedaluwarsa.']);
        }

        $expiresAt = Carbon::parse($resetToken->created_at)->addMinutes((int) config('auth.passwords.users.expire', 60));
        if ($expiresAt->isPast()) {
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return redirect()->route('password.request')
                ->withInput(['email' => $validated['email']])
                ->withErrors(['email' => 'Permintaan reset password sudah kedaluwarsa. Silakan ulangi prosesnya.']);
        }

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'captcha_answer'))
                ->withErrors(['email' => 'Akun tidak ditemukan.']);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Password berhasil direset. Silakan login dengan password baru.');
    }
}
