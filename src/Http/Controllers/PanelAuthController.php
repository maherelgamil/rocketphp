<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use MaherElGamil\Rocket\Auth\CreatePanelUser;
use MaherElGamil\Rocket\Auth\UpdatePanelProfile;
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PanelAuthController extends Controller
{
    public function __construct(private readonly PanelManager $panels) {}

    public function showLogin(Request $request): InertiaResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isLoginEnabled());

        return Inertia::render('rocket/login', [
            'panel' => $this->panelProps($panel),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isLoginEnabled());

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey($request, $credentials['email']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', ['seconds' => $seconds]),
            ]);
        }

        if (! Auth::guard($panel->getGuard())->attempt($credentials, (bool) $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended($panel->url('/'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $panel = $this->resolvePanel($request);

        Auth::guard($panel->getGuard())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($panel->url('login'));
    }

    public function showRegister(Request $request): InertiaResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isRegistrationEnabled());

        return Inertia::render('rocket/register', [
            'panel' => $this->panelProps($panel),
        ]);
    }

    public function register(Request $request, CreatePanelUser $action): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isRegistrationEnabled());

        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = $action->create($panel, $input);

        Auth::guard($panel->getGuard())->login($user);
        $request->session()->regenerate();

        return redirect($panel->url('/'));
    }

    public function showForgot(Request $request): InertiaResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isPasswordResetEnabled());

        return Inertia::render('rocket/forgot-password', [
            'panel' => $this->panelProps($panel),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isPasswordResetEnabled());

        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker($this->passwordBroker($panel))
            ->sendResetLink($request->only('email'));

        if ($status !== Password::ResetLinkSent) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return back()->with('status', __($status));
    }

    public function showReset(Request $request, string $token): InertiaResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isPasswordResetEnabled());

        return Inertia::render('rocket/reset-password', [
            'panel' => $this->panelProps($panel),
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isPasswordResetEnabled());

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker($this->passwordBroker($panel))->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect($panel->url('login'))->with('status', __($status));
    }

    public function showVerify(Request $request): InertiaResponse|RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isEmailVerificationEnabled());

        $user = Auth::guard($panel->getGuard())->user();

        if ($user && method_exists($user, 'hasVerifiedEmail') && $user->hasVerifiedEmail()) {
            return redirect($panel->url('/'));
        }

        return Inertia::render('rocket/verify-email', [
            'panel' => $this->panelProps($panel),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(Request $request, int|string $id, string $hash): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isEmailVerificationEnabled());

        $user = Auth::guard($panel->getGuard())->user();

        if (! $user || (string) $user->getKey() !== (string) $id) {
            throw new NotFoundHttpException;
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            throw new NotFoundHttpException;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect($panel->url('/'))->with('status', 'verified');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isEmailVerificationEnabled());

        $user = Auth::guard($panel->getGuard())->user();

        if ($user && method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'verification-link-sent');
    }

    public function showProfile(Request $request): InertiaResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isProfileEnabled());

        return Inertia::render('rocket/profile', [
            'panel' => $panel->toSharedProps(),
            'user' => Auth::guard($panel->getGuard())->user(),
        ]);
    }

    public function updateProfile(Request $request, UpdatePanelProfile $action): RedirectResponse
    {
        $panel = $this->resolvePanel($request);
        $this->ensure($panel->isProfileEnabled());

        $user = Auth::guard($panel->getGuard())->user();

        if (! $user) {
            throw new NotFoundHttpException;
        }

        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'current_password' => ['required_with:password', 'nullable', 'string'],
            'password' => ['nullable', 'confirmed', PasswordRule::defaults()],
        ]);

        if (! empty($input['password']) && ! Hash::check((string) $input['current_password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => trans('auth.password'),
            ]);
        }

        $action->update($panel, $user, $input);

        return back()->with('status', 'profile-updated');
    }

    private function resolvePanel(Request $request): Panel
    {
        $panelId = $request->route()?->defaults['panelId'] ?? null;

        if ($panelId === null) {
            throw new NotFoundHttpException('Rocket panel not resolved for this route.');
        }

        $panel = $this->panels->get($panelId);
        $this->panels->setCurrent($panelId);

        return $panel;
    }

    private function ensure(bool $condition): void
    {
        if (! $condition) {
            throw new NotFoundHttpException;
        }
    }

    private function throttleKey(Request $request, string $email): string
    {
        return Str::lower($email).'|'.$request->ip();
    }

    private function passwordBroker(Panel $panel): ?string
    {
        $provider = config('auth.guards.'.$panel->getGuard().'.provider');

        foreach ((array) config('auth.passwords', []) as $name => $broker) {
            if (($broker['provider'] ?? null) === $provider) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function panelProps(Panel $panel): array
    {
        return [
            'id' => $panel->id(),
            'brand' => $panel->getBrand(),
            'path' => $panel->getPath(),
            'theme' => $panel->getTheme(),
            'auth' => [
                'login_url' => $panel->isLoginEnabled() ? $panel->url('login') : null,
                'register_url' => $panel->isRegistrationEnabled() ? $panel->url('register') : null,
                'forgot_password_url' => $panel->isPasswordResetEnabled() ? $panel->url('forgot-password') : null,
            ],
        ];
    }
}
