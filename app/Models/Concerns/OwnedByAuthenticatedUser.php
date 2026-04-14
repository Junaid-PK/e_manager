<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait OwnedByAuthenticatedUser
{
    protected static function bootOwnedByAuthenticatedUser(): void
    {
        static::addGlobalScope('ownedByUser', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }
            $user = Auth::user();
            if ($user === null || $user->isAdmin()) {
                return;
            }
            $table = $builder->getModel()->getTable();
            $builder->where($table.'.user_id', $user->id);
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('user_id') !== null && $model->getAttribute('user_id') !== '') {
                return;
            }
            if (! Auth::check()) {
                return;
            }
            $model->setAttribute('user_id', Auth::id());
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
