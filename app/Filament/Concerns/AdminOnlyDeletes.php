<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AdminOnlyDeletes
{
    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
