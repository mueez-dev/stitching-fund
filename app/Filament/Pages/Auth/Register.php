<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Models\UserInvitation;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;

class Register extends BaseRegister
{
    // Livewire properties (must be public)
    public ?string $invitationCode = null;
    public ?array $invitationData = null;
    public ?UserInvitation $invitation = null;
    public bool $isDemoRegistration = false;

    // Mount method to load invitation from URL
    public function mount(): void
    {
        parent::mount();

        $this->invitationCode = request('invitation');
        $this->isDemoRegistration = request('demo') === 'true';
        
        // Store demo flag in session to persist across form method calls
        if ($this->isDemoRegistration) {
            session(['is_demo_registration' => true]);
        }

        if ($this->invitationCode) {
            $this->invitation = UserInvitation::where('unique_code', $this->invitationCode)
                ->first();

            if ($this->invitation) {
                if ($this->invitation->status === 'pending' &&
                    $this->invitation->expires_at > now() &&
                    !$this->invitation->accepted_at
                ) {
                    // Store invitation data
                    $this->invitationData = $this->invitation->toArray();

                    // Prefill form fields
                    $this->form->fill([
                        'email' => $this->invitationData['email'],
                        'role' => 'Investor',
                        'status' => 'active',
                        'invited_by' => $this->invitationData['invited_by']
                    ]);
                } else {
                    $this->handleInvalidOrUsedInvitation($this->invitationCode);
                }
            } else {
                $this->handleInvalidOrUsedInvitation($this->invitationCode);
            }
        } elseif ($this->isDemoRegistration) {
            // Demo registration
            $this->form->fill([
                'role' => 'Agency Owner',
                'status' => 'active',
                'is_demo' => true,
                'demo_expires_at' => now()->addDays(3)
            ]);
        } else {
            // Direct registration for Agency Owner
            $this->form->fill([
                'role' => 'Agency Owner',
                'status' => 'inactive'
            ]);
        }
    }

    // Show notification & redirect if invitation invalid
    protected function handleInvalidOrUsedInvitation(string $invitationCode): void
    {
        $invitation = UserInvitation::where('unique_code', $invitationCode)->first();
        $message = 'Invalid invitation code.';

        if ($invitation) {
            if ($invitation->accepted_at || $invitation->status === 'accepted') {
                $message = 'This invitation has already been used.';
            } elseif ($invitation->expires_at < now()) {
                $message = 'This invitation has expired.';
                $invitation->update(['status' => 'expired']);
            } elseif ($invitation->status !== 'pending') {
                $message = 'This invitation is no longer valid.';
            }
        }

        Notification::make()
            ->title('Invitation Not Valid')
            ->body($message)
            ->danger()
            ->send();

        $this->redirect(route('filament.admin.auth.register'));
    }

    // CRITICAL: Override register method to prevent auto-login
    public function register(): ?RegistrationResponse
    {
        $data = $this->form->getState();

        // Create the user without logging them in
        $user = $this->handleRegistration($data);

        // DO NOT call parent::register() or auth()->login()
        
        // Create custom response to redirect to verification page
        $response = new class implements RegistrationResponse {
            public function toResponse($request)
            {
                return redirect('/admin/email-verification');
            }
        };
        
        return $response;
    }

    // Form fields
    public function form(Schema $schema): Schema
    {
        $invitation = $this->invitation;
        $isInvitation = ! empty($invitation);
        // Check URL parameter first, then session as fallback
        $isDemoRegistration = request('demo') === 'true' || session('is_demo_registration', false);

        $components = [
            $this->getNameFormComponent(),

            $this->getEmailFormComponent()
                ->disabled($isInvitation)
                ->dehydrated(true),

            $this->getPasswordFormComponent(),

            $this->getPasswordConfirmationFormComponent(),

            TextInput::make('role')
                ->label('Role')
                ->default($isInvitation ? 'Investor' : 'Agency Owner')
                ->disabled(true)
                ->dehydrated(true)
                ->required()
                ->helperText($isInvitation
                    ? 'You are registering as an Investor via invitation link.'
                    : ($isDemoRegistration 
                        ? 'You are registering as a Demo Agency.'
                        : 'You are registering as an Agency Owner.')),
        ];

        // Add hidden invited_by field for invitation
        if ($isInvitation) {
            $components[] = TextInput::make('invited_by')
                ->default($invitation->invited_by)
                ->hidden()
                ->dehydrated(true);
        }

        // Add hidden demo fields
        if ($isDemoRegistration) {
            $components[] = TextInput::make('is_demo')
                ->default(true)
                ->hidden()
                ->dehydrated(true);
            
            $components[] = TextInput::make('demo_expires_at')
                ->default(now()->addDays(3))
                ->hidden()
                ->dehydrated(true);
        }

        return $schema
            ->schema($components)
            ->statePath('data');
    }

    // Before user is created, merge invitation data
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $isDemoRegistration = request('demo') === 'true' || session('is_demo_registration', false);
        
        if ($this->invitationData) {
            return array_merge($data, [
                'email' => $this->invitationData['email'],
                'role' => 'Investor',
                'status' => 'inactive',
                'invited_by' => $this->invitationData['invited_by'] ?? null,
                'company_name' => $this->invitationData['company_name'] ?? null,
            ]);
        }

        if ($isDemoRegistration || ($data['is_demo'] ?? false)) {
            return array_merge($data, [
                'role' => 'Agency Owner',
                'status' => 'active',
                'is_demo' => true,
                'demo_expires_at' => now()->addDays(3)
            ]);
        }

        return array_merge($data, [
            'role' => 'Agency Owner',
            'status' => 'inactive'
        ]);
    }

    // Override registration to handle invited_by and mark invitation accepted
    protected function handleRegistration(array $data): User
    {
        // Get the mutated data (includes invited_by and demo fields)
        $mutatedData = $this->mutateFormDataBeforeCreate($data);
        
        // Create user manually with all fields
        $user = User::create([
            'name' => $mutatedData['name'],
            'email' => $mutatedData['email'],
            'password' => Hash::make($data['password']), // Hash password here
            'role' => $mutatedData['role'],
            'status' => $mutatedData['status'] ?? 'inactive',
            'invited_by' => $mutatedData['invited_by'] ?? null,
            'company_name' => $mutatedData['company_name'] ?? null,
            'is_demo' => $mutatedData['is_demo'] ?? false,
            'demo_expires_at' => $mutatedData['demo_expires_at'] ?? null,
        ]);
        
        // Mark invitation accepted and link user
        if ($this->invitation) {
            $this->invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'user_id' => $user->id,
            ]);
        }

        // Generate verification code and send email
        $this->sendEmailVerification($user);

        // Store email in session for verification page
        session(['verification_email' => $user->email]);
        
        // Ensure user is logged out (prevent any auto-login)
        Auth::logout();

        // Return user WITHOUT logging them in
        return $user;
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('register')
                ->label('Register')
                ->submit('register')
                ->color('primary'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('email-verification');
    }

    protected function sendEmailVerification(User $user): void
    {
        // Generate 6-digit verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashedCode = Hash::make($code);

        // Update user with verification code using raw database update
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update([
                'email_verification_code' => $hashedCode,
                'email_verification_expires_at' => now()->addMinutes(15),
            ]);

        // Send verification email
        try {
            $user->notify(new \App\Notifications\EmailVerificationNotification($code));

            Notification::make()
                ->title('Registration Successful')
                ->body('A verification code has been sent to your email.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Email Error')
                ->body('Could not send verification email. Please contact support.')
                ->warning()
                ->send();
        }
    }
}