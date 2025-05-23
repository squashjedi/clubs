<?php

namespace App\Enums;

enum Status: int
{
    case Pending = 0;
    case Approved = 1;
    case Negative = 2;

    public function label()
    {
        return match ($this) {
            static::Pending => 'Pending',
            static::Approved => 'Approved',
        };
    }

    public function icon()
    {
        return match ($this) {
            static::Negative => '',
            static::Pending => 'hourglass',
            static::Approved => 'check',
        };
    }

    public function color()
    {
        return match ($this) {
            static::Negative => 'zinc',
            static::Pending => 'amber',
            static::Approved => 'green',
        };
    }

    public function buttonLabel()
    {
        return match ($this) {
            static::Negative => 'Join',
            static::Pending => 'Pending',
            static::Approved => 'Approved',
        };
    }

    public function buttonIcon()
    {
        return match ($this) {
            static::Negative => 'user',
            static::Pending => 'hourglass',
            static::Approved => 'check',
        };
    }

    public function buttonVariant()
    {
        return match ($this) {
            static::Negative => 'primary',
            static::Pending => 'filled',
            static::Approved => 'filled',
        };
    }
}