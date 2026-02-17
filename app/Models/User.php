<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Filament\Notifications\Notification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Models\Contracts\FilamentUser;
use App\Notifications\NewUserWaitingApproval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use App\Enums\SubscriptionStatus;
use Carbon\Carbon;

class User extends Authenticatable implements FilamentUser, MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, CanResetPassword, MustVerifyEmail;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'invited_by',
        'is_demo',
        'demo_expires_at',
        'email_verified_at',
        'email_verification_code',
        'email_verification_expires_at',
        'subscription_status',
        'subscription_expires_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
    'is_demo' => 'boolean',
    'demo_expires_at' => 'datetime',
    'email_verified_at' => 'datetime',
    'email_verification_expires_at' => 'datetime',
    'subscription_expires_at' => 'datetime',
    'subscription_status' => SubscriptionStatus::class,
    ];


    // No boot method needed - notifications handled in CreateUsers page

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }

    /**
     * Check if the user is an admin (returns 1 for admin, 0 for non-admin)
     */
    public function isAdmin(): int
    {
        return ($this->role === 'Agency Owner' || $this->role === 'Super Admin') ? 1 : 0;
    }

    /**
     * Check if the user is an agency owner or admin
     */
    public function isAgencyOwner(): bool
    {
        return $this->role === 'Agency Owner' || $this->role === 'Super Admin';
    }
    
    /**
     * Scope to filter users based on current user's role
     */
    public function scopeForCurrentUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? Auth::user();
        
        // If no authenticated user, show nothing
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        
        // Super Admin sees all users
        if ($user->role === 'Super Admin') {
            return $query;
        }
        
        // Agency Owner sees only themselves
        if ($user->role === 'Agency Owner') {
            return $query->where('id', $user->id);
        }
        
        // Regular users only see themselves
        return $query->where('id', $user->id);
    }
    
    /**
     * Check if current user can view this user record
     */
    public function canBeViewedBy(?User $viewer = null): bool
    {
        $viewer = $viewer ?? Auth::user();
        
        if (!$viewer) {
            return false;
        }
        
        // Super Admin can view anyone
        if ($viewer->role === 'Super Admin') {
            return true;
        }
        
        // Agency Owner and Regular users can only view themselves
        return $this->id === $viewer->id;
    }
    
    /**
     * Check if current user can edit this user record
     */
    public function canBeEditedBy(?User $editor = null): bool
    {
        $editor = $editor ?? Auth::user();
        
        if (!$editor) {
            return false;
        }
        
        // Super Admin can edit anyone
        if ($editor->role === 'Super Admin') {
            return true;
        }
        
        // Agency Owner and Regular users can only edit themselves
        return $this->id === $editor->id;
    }
    
    /**
     * Check if current user can delete this user record
     */
    public function canBeDeletedBy(?User $deleter = null): bool
    {
        $deleter = $deleter ?? Auth::user();
        
        if (!$deleter) {
            return false;
        }
        
        // Can't delete yourself
        if ($this->id === $deleter->id) {
            return false;
        }
        
        // Only Super Admin can delete users
        if ($deleter->role === 'Super Admin') {
            return true;
        }
        
        // Agency Owners and Regular users can't delete anyone
        return false;
    }
    
    public function agencyOwner()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function investors()
    {
        return $this->hasMany(User::class, 'invited_by');
    }
    protected static function booted()
    {
        static::saved(function ($user) {
            // Send notification when new user is created with inactive status
            if ($user->status === 'inactive' && ($user->wasRecentlyCreated || $user->wasChanged('status'))) {
                
                $superAdmin = User::where('role', 'Super Admin')->first();

                if ($superAdmin) {
                    Notification::make()
                        ->title('New User Registration')
                        ->body("New user '{$user->name}' has registered and needs approval.")
                        ->warning()
                        ->sendToDatabase($superAdmin);
                }
            }
        });
    }
    
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' && 
               $this->subscription_expires_at && 
               $this->subscription_expires_at > now();
    }

    public function canImpersonate(): bool
    {
        return $this->role === 'Super Admin';
    }

    public function canBeImpersonated(): bool
    {
        return $this->role !== 'Super Admin';
    }

    public function getSubscriptionState(): string
    {
        // Skip for demo users
        if ($this->is_demo) {
            return 'active';
        }

        // If no subscription at all
        if (!$this->subscription_expires_at) {
            return 'locked';
        }

        // If subscription is expired
        if ($this->subscription_expires_at->isPast()) {
            // For now, treat expired as grace period (you can adjust this logic)
            return 'expired_grace';
        }

        // If subscription is expiring soon (e.g., within 7 days)
        if ($this->subscription_expires_at->diffInDays(now()) <= 7) {
            return 'expiring';
        }

        return 'active';
    }

    public function getGraceEndsAt(): ?Carbon
    {
        // Return a default grace period end date (7 days from now) when in grace period
        if ($this->getSubscriptionState() === 'expired_grace') {
            return now()->addDays(7);
        }
        return null;
    }

    public function isInGracePeriod(): bool
    {
        return $this->getSubscriptionState() === 'expired_grace';
    }

    public function isLocked(): bool
    {
        return $this->getSubscriptionState() === 'locked';
    }
}