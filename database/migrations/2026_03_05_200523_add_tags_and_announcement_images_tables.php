<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();
        });

        Schema::create('announcement_tag', function (Blueprint $table): void {
            $table->uuid('announcement_id');
            $table->uuid('tag_id');

            $table->foreign('announcement_id')->references('id')->on('announcements')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
            $table->primary(['announcement_id', 'tag_id']);
        });

        Schema::create('announcement_images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('announcement_id');
            $table->string('path');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('announcement_id')->references('id')->on('announcements')->cascadeOnDelete();
            $table->index('announcement_id');
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
        Schema::dropIfExists('announcement_images');
        Schema::dropIfExists('announcement_tag');
        Schema::dropIfExists('tags');
    }
};
