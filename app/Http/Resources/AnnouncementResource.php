<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \App\Models\Announcement
 */
final class AnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'category' => $this->category,
            'image' => $this->image ? (str_starts_with($this->image, 'http') ? $this->image : Storage::disk('public')->url($this->image)) : null,
            'images' => AnnouncementImageResource::collection($this->whenLoaded('images')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'company' => new CompanyResource($this->whenLoaded('company')),
            'author' => new UserResource($this->whenLoaded('author')),
            'comments_count' => $this->whenCounted('comments'),
            'reactions_count' => $this->whenCounted('reactions'),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'reactions' => ReactionResource::collection($this->whenLoaded('reactions')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
