<?php

namespace App\Filament\Pages\Auth;

use App\Services\OnboardingService;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        Select::make('timezone')
                            ->label('Your Timezone')
                            ->options(
                                collect(timezone_identifiers_list())
                                    ->mapWithKeys(fn ($tz) => [$tz => $tz])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required()
                            ->default('America/New_York'),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function afterCreate(): void
    {
        $user = $this->getUser();

        if ($user) {
            $user->update([
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]);

            app(OnboardingService::class)->seedDefaultLifeAreas($user);
        }
    }

    protected function getUser(): ?Model
    {
        return $this->user ?? null;
    }
}
