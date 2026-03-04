<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreAnnouncementRequest;
use App\Http\Requests\Api\V1\StoreCommentRequest;
use App\Http\Requests\Api\V1\StoreReactionRequest;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ReactionResource;
use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AnnouncementController extends ApiController
{
    public function index(): JsonResponse
    {
        $announcements = Announcement::query()
            ->published()
            ->with(['company', 'author'])
            ->withCount(['comments', 'reactions'])
            ->paginate(15);

        return $this->success(AnnouncementResource::collection($announcements)->response()->getData(true));
    }

    public function show(string $id): JsonResponse
    {
        $announcement = Announcement::query()
            ->with([
                'company',
                'author',
                'comments' => fn ($q) => $q->whereNull('parent_id')->with(['user', 'replies.user'])->latest(),
                'reactions.user',
            ])
            ->withCount(['comments', 'reactions'])
            ->find($id);

        if (! $announcement) {
            return $this->notFound('Annonce non trouvée');
        }

        return $this->success(new AnnouncementResource($announcement));
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $company = $user->companies()->first();

        if (! $company) {
            return $this->forbidden('Vous devez être associé à une compagnie');
        }

        $data = $request->validated();
        $data['company_id'] = $company->id;
        $data['author_id'] = $user->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('announcements', 'public');
        }

        if ($data['is_published'] ?? false) {
            $data['published_at'] = now();
        }

        $announcement = Announcement::query()->create($data);

        return $this->created(
            new AnnouncementResource($announcement->load(['company', 'author'])),
            'Annonce créée avec succès',
        );
    }

    public function update(StoreAnnouncementRequest $request, string $id): JsonResponse
    {
        $announcement = Announcement::query()->find($id);

        if (! $announcement) {
            return $this->notFound('Annonce non trouvée');
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('announcements', 'public');
        }

        if (($data['is_published'] ?? false) && ! $announcement->published_at) {
            $data['published_at'] = now();
        }

        $announcement->update($data);

        return $this->success(
            new AnnouncementResource($announcement->fresh(['company', 'author'])),
            'Annonce mise à jour',
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $announcement = Announcement::query()->find($id);

        if (! $announcement) {
            return $this->notFound('Annonce non trouvée');
        }

        $announcement->delete();

        return $this->success(message: 'Annonce supprimée');
    }

    public function comment(StoreCommentRequest $request, string $id): JsonResponse
    {
        $announcement = Announcement::query()->find($id);

        if (! $announcement) {
            return $this->notFound('Annonce non trouvée');
        }

        /** @var User $user */
        $user = $request->user();

        $comment = Comment::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'parent_id' => $request->parent_id,
            'body' => $request->body,
        ]);

        return $this->created(
            new CommentResource($comment->load('user')),
            'Commentaire ajouté',
        );
    }

    public function react(StoreReactionRequest $request, string $id): JsonResponse
    {
        $announcement = Announcement::query()->find($id);

        if (! $announcement) {
            return $this->notFound('Annonce non trouvée');
        }

        /** @var User $user */
        $user = $request->user();

        $existingReaction = Reaction::query()
            ->where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type->value === $request->type) {
                $existingReaction->delete();

                return $this->success(message: 'Réaction retirée');
            }

            $existingReaction->update(['type' => $request->type]);

            return $this->success(new ReactionResource($existingReaction->fresh('user')), 'Réaction mise à jour');
        }

        $reaction = Reaction::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'type' => $request->type,
        ]);

        return $this->created(new ReactionResource($reaction->load('user')), 'Réaction ajoutée');
    }
}
