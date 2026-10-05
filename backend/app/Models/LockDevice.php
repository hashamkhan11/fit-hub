<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LockDevice extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'gym_id',
        'name',
        'token_hash',
        'last_seen_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected $appends = [
        'is_online',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(LockCommand::class);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subSeconds(30));
    }

    /**
     * Makes a new device token, saves only its hash, and returns it once.
     * Show it to the user right away — it can't be seen again after this.
     */
    public static function issueToken(int $gymId, string $name): array
    {
        $plainToken = Str::random(40);

        $device = static::create([
            'gym_id' => $gymId,
            'name' => $name,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return [$device, $plainToken];
    }

    public function regenerateToken(): string
    {
        $plainToken = Str::random(40);

        $this->update(['token_hash' => hash('sha256', $plainToken)]);

        return $plainToken;
    }

    public static function findByToken(string $plainToken): ?self
    {
        return static::where('token_hash', hash('sha256', $plainToken))->first();
    }
}
