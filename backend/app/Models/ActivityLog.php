<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use BelongsToGym;

    public $timestamps = false;

    protected $fillable = [
        'gym_id',
        'user_id',
        'action',
        'description',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Logs an action done by the currently logged-in staff/owner user.
     */
    public static function record(string $action, string $description): void
    {
        static::create([
            'gym_id' => auth()->user()?->gym_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
