<?php

namespace Stephenjude\FilamentTwoFactorAuthentication\Pages;

use App\Models\User;
use App\Services\UserService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\LoginResponse;
use Illuminate\Contracts\Support\Htmlable;
use Stephenjude\FilamentTwoFactorAuthentication\Events\TwoFactorAuthenticationChallenged;
use Stephenjude\FilamentTwoFactorAuthentication\Events\TwoFactorAuthenticationFailed;
use Stephenjude\FilamentTwoFactorAuthentication\TwoFactorAuthenticationProvider;

class Challenge extends BaseSimplePage
{
    protected static string $view = 'filament-two-factor-authentication::pages.challenge';

    public ?array $data = [];

    public function getTitle(): string | Htmlable
    {
        return __('Two Factor Authentication');
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        $model = Filament::auth()->getProvider()->getModel();

        $user = $model::find(session('login.id'));

        if (! $user) {
            redirect()->to(filament()->getCurrentPanel()?->getLoginUrl());

            return;
        }

        $this->form->fill();

        TwoFactorAuthenticationChallenged::dispatch($user);
    }

    public function recoveryAction(): Action
    {
        return Action::make('recovery')
            ->link()
            ->label(__('use a recovery code'))
            ->url(
                filament()->getCurrentPanel()->route(
                    'two-factor.recovery'
                )
            );
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);

            $this->form->getState();

            UserService::saveTrustedDevice(User::where('id', session('login.id'))->first());

            Filament::auth()->loginUsingId(
                id: session('login.id'),
                remember: session('login.remember')
            );

            session()->forget(['login.id', 'login.remember']);

            session()->regenerate();

            if ($this->form->getState()['remember']) {
                cookie()->queue(
                    'remember',
                    encrypt($this->form->getState()['remember']),
                    60 * 48
                );
            }

            return app(LoginResponse::class);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }
    }

    public function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        TextInput::make('code')
                            ->hiddenLabel()
                            ->hint(
                                __(
                                    'Please confirm access to your account by entering the authentication code provided by your authenticator application.'
                                )
                            )
                            ->label(__('Code'))
                            ->required()
                            ->autocomplete()
                            ->rules([
                                fn () => function (string $attribute, $value, $fail) {
                                    $model = Filament::auth()->getProvider()->getModel();

                                    $user = $model::find(session('login.id'));

                                    if (is_null($user)) {
                                        $fail(__('The provided two factor authentication code was invalid.'));

                                        redirect()->to(filament()->getCurrentPanel()->getLoginUrl());

                                        return;
                                    }

                                    $isValidCode = app(TwoFactorAuthenticationProvider::class)->verify(
                                        secret: decrypt($user->two_factor_secret),
                                        code: $value
                                    );

                                    if (! $isValidCode) {
                                        $fail(__('The provided two factor authentication code was invalid.'));

                                        event(new TwoFactorAuthenticationFailed($user));
                                    }
                                },
                            ]),
                        Checkbox::make('remember')
                            ->label(__('Remember this choice for 2 days'))
                            ->helperText(__('For your security, please do not check this option on a shared device.'))
                            ->default(session('login.remember', false)),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
}
