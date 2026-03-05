<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $announcement_id
 * @property string $path
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AnnouncementImage extends Model
{
    use HasUuids;

    protected $fillable = [
        'announcement_id',
        'path',
        'order',
    ];

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
