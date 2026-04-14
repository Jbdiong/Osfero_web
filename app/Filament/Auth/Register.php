<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;
use Filament\Events\Auth\Registered;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Credentials')
                        ->schema([
                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(User::class)
                                ->label('Email Address'),
                            TextInput::make('password')
                                ->password()
                                ->required()
                                ->revealable()
                                ->confirmed()
                                ->minLength(8)
                                ->label('Password'),
                            TextInput::make('password_confirmation')
                                ->password()
                                ->required()
                                ->revealable()
                                ->label('Confirm Password'),
                        ])
                        ->afterValidation(function (Get $get) {
                            $this->sendVerificationCode($get('email'));
                        }),
                    
                    Wizard\Step::make('Verification')
                        ->schema([
                            TextInput::make('verification_code')
                                ->label('Verification Code')
                                ->required()
                                ->placeholder('Enter the code sent to your email')
                                ->hintAction(
                                    Action::make('resend')
                                        ->label('Resend Code')
                                        ->action(function (Get $get) {
                                            $this->sendVerificationCode($get('email'));
                                        })
                                ),
                        ])
                        ->afterValidation(function (Get $get, $state) {
                            $code = Session::get('registration_otp');
                            $email = Session::get('registration_email');
                            
                            if ($get('email') !== $email || $get('verification_code') != $code) {
                                throw ValidationException::withMessages([
                                    'data.verification_code' => 'Invalid verification code.',
                                ]);
                            }
                        }),

                    Wizard\Step::make('Organization')
                        ->schema([
                            TextInput::make('invitation_code')
                                ->label('Invitation Code')
                                ->required()
                                ->exists(table: Tenant::class, column: 'code')
                                ->validationAttribute('invitation code')
                                ->rule(function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $tenant = Tenant::findByInvitationCode($value);
                                        if (! $tenant) {
                                            $fail('The invitation code is invalid or has expired.');
                                        }
                                    };
                                }),
                        ]),

                    Wizard\Step::make('Profile')
                        ->schema([
                            TextInput::make('name')
                                ->label('How should we call you?')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('John Doe'),
                        ]),
                ])->submitAction(new \Illuminate\Support\HtmlString(view('filament.auth.button')->render()))
            ]);
    }

    protected function sendVerificationCode($email)
    {
        $code = rand(100000, 999999);
        Session::put('registration_otp', $code);
        Session::put('registration_email', $email);

        try {
            Mail::raw("Your verification code is: {$code}", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Verify your email address');
            });
            
            Notification::make()
                ->title('Verification code sent')
                ->success()
                ->send();
        } catch (\Exception $e) {
             Notification::make()
                ->title('Failed to send verification code')
                ->body('Please check your mail configuration.')
                ->danger()
                ->send();
        }
    }

    protected function handleRegistration(array $data): User
    {
        $staffRole = \App\Models\SystemRole::where('role', 'Staff')->first();
        
        $tenant = Tenant::findByInvitationCode($data['invitation_code']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'last_active_tenant_id' => $tenant->id,
        ]);
        
        // Attach to the tenant via the pivot table
        $user->tenants()->attach($tenant->id, [
            'role_id' => $staffRole ? $staffRole->id : 4,
            'display_name' => $data['name'],
        ]);
        
        // Clean up session
        Session::forget(['registration_otp', 'registration_email']);

        event(new Registered($user));

        return $user;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
