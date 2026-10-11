<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(config('services.google.client_id') && config('services.google.client_secret'), 503, 'Google login belum dikonfigurasi.');
        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('login')->withErrors(['google' => 'Login Google gagal. Silakan coba lagi.']);
        }

        $email = $google->getEmail();
        abort_unless($email && filter_var($email, FILTER_VALIDATE_EMAIL), 422, 'Google tidak memberikan email valid.');

        $existing = User::where('google_id', $google->getId())->first();
        if (! $existing) {
            // Never silently link an existing account by email: it must be linked by a verified, authenticated user.
            abort_if(User::where('email', $email)->exists(), 409, 'Email sudah terdaftar. Hubungi admin untuk menautkan Google.');
            $existing = User::create([
                'name' => $google->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $google->getId(),
                'avatar_url' => $google->getAvatar(),
                'email_verified_at' => now(),
                'password' => null,
                'platform_role' => 'fulltime',
            ]);
        }

        Auth::login($existing, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('home');
    }
}
