<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case WARNING = 'warning';
    case EXPIRED = 'expired';
    case LOCKED = 'locked';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::WARNING => 'Expiring Soon',
            self::EXPIRED => 'Expired (View Only)',
            self::LOCKED => 'Account Locked',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::WARNING => 'warning',
            self::EXPIRED => 'danger',
            self::LOCKED => 'gray',
        };
    }
}