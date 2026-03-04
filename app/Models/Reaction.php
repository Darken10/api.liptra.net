<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReactionType;
use Database\Factories\ReactionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $announcement_id
 * @property string $user_id
 * @property ReactionType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'type' => ReactionType::class,
        ];
    }

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
