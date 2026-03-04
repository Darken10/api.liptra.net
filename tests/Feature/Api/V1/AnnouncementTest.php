<?php

declare(strict_types=1);

use App\Enums\ReactionType;
use App\Models\Announcement;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Reaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Announcement Listing', function (): void {
    it('lists published announcements', function (): void {
        $company = Company::factory()->create();
        Announcement::factory()->count(3)->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
        Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => false,
            'published_at' => null,
        ]);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertSuccessful();
        expect($response->json('data.data'))->toHaveCount(3);
    });

    it('shows a single announcement with comments and reactions', function (): void {
        $company = Company::factory()->create();
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        Comment::factory()->count(2)->create(['announcement_id' => $announcement->id]);
        Reaction::factory()->count(3)->create(['announcement_id' => $announcement->id]);

        $response = $this->getJson("/api/v1/announcements/{$announcement->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $announcement->id)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'content', 'comments', 'reactions'],
            ]);
    });
});

describe('Announcement Management', function (): void {
    it('allows admin to create announcement', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $company->users()->attach($user->id, ['role' => 'admin']);

        test()->seed(RolePermissionSeeder::class);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->postJson('/api/v1/announcements', [
            'title' => 'Nouvelle ligne Ouaga-Bobo',
            'content' => 'Nous lançons une nouvelle ligne directe entre Ouagadougou et Bobo-Dioulasso.',
            'is_published' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('announcements', ['title' => 'Nouvelle ligne Ouaga-Bobo']);
    });

    it('forbids regular user from creating announcement', function (): void {
        $user = User::factory()->create();
        test()->seed(RolePermissionSeeder::class);
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/v1/announcements', [
            'title' => 'Test',
            'content' => 'Test content',
        ]);

        $response->assertForbidden();
    });
});

describe('Comments', function (): void {
    it('adds a comment to an announcement', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/announcements/{$announcement->id}/comments", [
            'body' => 'Super nouvelle ! Merci pour cette info.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'body' => 'Super nouvelle ! Merci pour cette info.',
        ]);
    });

    it('adds a reply to a comment', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $parentComment = Comment::factory()->create(['announcement_id' => $announcement->id]);

        $response = $this->actingAs($user)->postJson("/api/v1/announcements/{$announcement->id}/comments", [
            'body' => 'Je suis d\'accord !',
            'parent_id' => $parentComment->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'parent_id' => $parentComment->id,
        ]);
    });
});

describe('Reactions', function (): void {
    it('adds a reaction to an announcement', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/announcements/{$announcement->id}/reactions", [
            'type' => ReactionType::Like->value,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reactions', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'type' => ReactionType::Like->value,
        ]);
    });

    it('toggles reaction off when same type sent again', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        Reaction::factory()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'type' => ReactionType::Like,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/announcements/{$announcement->id}/reactions", [
            'type' => ReactionType::Like->value,
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseMissing('reactions', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);
    });

    it('changes reaction type when different type sent', function (): void {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $announcement = Announcement::factory()->create([
            'company_id' => $company->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        Reaction::factory()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'type' => ReactionType::Like,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/announcements/{$announcement->id}/reactions", [
            'type' => ReactionType::Love->value,
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('reactions', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'type' => ReactionType::Love->value,
        ]);
    });
});
