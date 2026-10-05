<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Auto-filters queries by gym_id when a gym owner/staff member is logged in,
 * so one gym's data can never leak into another gym's view. Doesn't affect
 * platform admins or the mobile app's member login — they filter manually.
 */
trait BelongsToGym
{
    protected static function bootBelongsToGym(): void
    {
        static::addGlobalScope('gym', function (Builder $builder) {
            if (Auth::guard('web')->check()) {
                $builder->where(
                    $builder->getModel()->getTable().'.gym_id',
                    Auth::guard('web')->user()->gym_id
                );
            }
        });

        static::creating(function ($model) {
            if (empty($model->gym_id) && Auth::guard('web')->check()) {
                $model->gym_id = Auth::guard('web')->user()->gym_id;
            }
        });
    }
}
