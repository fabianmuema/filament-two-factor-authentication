<div>
    <style>
        :root {
            --primary-color: {{  tenant('color') }};
            --success-color: {{  tenant('color') }};
        }

        .two-factor-section {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.03),
                0 10px 15px -3px rgba(0, 0, 0, 0.03),
                0 20px 25px -5px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .two-factor-section:hover {
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.05),
                0 10px 15px -3px rgba(0, 0, 0, 0.05),
                0 20px 25px -5px rgba(0, 0, 0, 0.03);
        }

        .section-header {
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            position: relative;
            letter-spacing: -0.025em;
        }

        .section-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .qr-container {
            display: flex;
            justify-content: center;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: linear-gradient(to right, rgba(5, 150, 105, 0.03), rgba(16, 185, 129, 0.03));
            border-radius: 12px;
        }

        .recovery-codes-container {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1.25rem;
            margin: 1.25rem 0;
            border: 1px solid #f1f5f9;
        }

        .recovery-code {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--text-primary);
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .recovery-code:last-child {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .action-buttons button {
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .primary-button {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 1px 2px 0 rgba(5, 150, 105, 0.05);
        }

        .primary-button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.1), 
                        0 2px 4px -1px rgba(5, 150, 105, 0.06);
        }

        .secondary-button {
            background-color: #f1f5f9;
            color: var(--text-primary);
        }

        .secondary-button:hover {
            background-color: #e2e8f0;
            transform: translateY(-1px);
        }
    </style>

    <x-filament::section :aside="$aside" class="two-factor-section">
        <x-slot name="heading">
            <div class="section-header">
                <h2 class="section-title">{{__('Two Factor Authentication')}}</h2>
                <p class="section-description">{{__('Add additional security to your account using two factor authentication.')}}</p>
            </div>
        </x-slot>

        <div class="two-factor-content">
            @if($this->isConfirmingSetup)
                <h2 class="text-xl font-medium mb-4">
                    Finish enabling two factor authentication.
                </h2>

                <p class="text-sm mb-4">
                    When two factor authentication is enabled, you will be prompted for a secure, random token during
                    authentication. You may retrieve this token from your phone's Google Authenticator application.
                </p>

                <p class="text-sm font-semibold mb-4">
                    To finish enabling two factor authentication, scan the following QR code using your phone's
                    authenticator application or enter the setup key and provide the generated OTP code.
                </p>

                <div class="qr-container">
                    {!! $this->getUser()->twoFactorQrCodeSvg() !!}
                </div>

                <form wire:submit="confirmSetup">
                    <div class="mb-4">
                        {{ $this->form }}
                    </div>
                    <div class="action-buttons">
                        {{$this->confirmSetup}}
                        {{$this->cancelSetup}}
                    </div>
                </form>
            @elseif($this->enableTwoFactorAuthentication->isVisible())
                <h2 class="text-xl font-medium mb-4">
                    You have not enabled two factor authentication.
                </h2>

                <p class="text-sm mb-4">
                    When two factor authentication is enabled, you will be prompted for a secure, random token during
                    authentication. You may retrieve this token from your phone's Google Authenticator application.
                </p>

                <div class="action-buttons">
                    {{$this->enableTwoFactorAuthentication}}
                </div>
            @elseif($this->disableTwoFactorAuthentication->isVisible())
                <h2 class="text-xl font-medium mb-4">You have enabled two factor authentication.</h2>

                <p class="text-sm mb-4">
                    Store these recovery codes in a secure password manager. They can be used to recover
                    access to your account if your two factor authentication device is lost.
                </p>

                <div class="recovery-codes-container">
                    @foreach($this->getUser()->recoveryCodes() as $code)
                        <div class="recovery-code">{{$code}}</div>
                    @endforeach
                </div>

                <div class="action-buttons">
                    {{$this->generateNewRecoveryCodes}}
                    {{$this->disableTwoFactorAuthentication}}
                </div>
            @endif
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</div>
