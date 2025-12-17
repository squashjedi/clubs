<?php

namespace App\Enums;

enum ModelStatus: string
{
    case Active = 'active';
    case Trashed = 'trashed';
    case All = 'all';

    public function label()
    {
        return match ($this) {
            static::Active => 'active',
            static::Trashed => 'archived',
            static::All => '',
        };
    }
}