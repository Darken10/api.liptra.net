<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AnnouncementController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()->with('company', 'author', 'tags', 'images')->withCount('comments', 'reactions');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        $announcements = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success(AnnouncementResource::collection($announcements)->response()->getData(true));
    }

    public function show(Announcement $announcement): JsonResponse
    {
        $announcement->load('company', 'author', 'comments.user', 'reactions', 'tags', 'images');

        return $this->success(new AnnouncementResource($announcement));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:5120'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
            'is_published' => ['boolean'],
        ]);

        $validated['author_id'] = $request->user()->id;
        $validated['slug'] = Str::slug($validated['title']).'-'.Str::random(6);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('announcements', 'public');
        }

        if ($request->boolean('is_published') && ! isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        unset($validated['images'], $validated['tag_ids']);

        $announcement = Announcement::query()->create($validated);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $announcement->images()->create([
                    'path' => $file->store('announcements', 'public'),
                    'order' => $index,
                ]);
            }
        }

        if ($request->has('tag_ids')) {
            $announcement->tags()->sync($request->input('tag_ids', []));
        }

        $announcement->load('company', 'author', 'tags', 'images');

        return $this->created(new AnnouncementResource($announcement));
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'image' => ['nullable', 'string'],
            'is_published' => ['boolean'],
        ]);

        if (isset($validated['title']) && $validated['title'] !== $announcement->title) {
            $validated['slug'] = Str::slug($validated['title']).'-'.Str::random(6);
        }

        if ($request->boolean('is_published') && ! $announcement->is_published) {
            $validated['published_at'] = now();
        }

        $announcement->update($validated);
        $announcement->load('company', 'author');

        return $this->success(new AnnouncementResource($announcement));
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return $this->noContent();
    }
}
