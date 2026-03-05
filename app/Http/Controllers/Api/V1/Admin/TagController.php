<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class TagController extends ApiController
{
    public function index(): JsonResponse
    {
        $tags = Tag::query()->orderBy('name')->get();

        return $this->success(TagResource::collection($tags));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $tag = Tag::query()->create($validated);

        return $this->created(new TagResource($tag));
    }

    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return $this->noContent();
    }
}
