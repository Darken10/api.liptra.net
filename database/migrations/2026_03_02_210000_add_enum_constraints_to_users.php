<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        // MySQL: change column type to native ENUM
        if ($driver === 'mysql') {
            try {
                DB::statement("ALTER TABLE users MODIFY gender ENUM('male','female','non_binary','not_specified') NULL");
            } catch (\Throwable $e) {
                // ignore if fails
            }

            try {
                DB::statement("ALTER TABLE users MODIFY status ENUM('active','inactive','pending','banned') NULL");
            } catch (\Throwable $e) {
                // ignore if fails
            }

            return;
        }

        // PostgreSQL: create types and alter column types
        if ($driver === 'pgsql') {
            try {
                DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gender_enum') THEN CREATE TYPE gender_enum AS ENUM ('male','female','non_binary','not_specified'); END IF; END $$;");
                DB::statement("DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'status_enum') THEN CREATE TYPE status_enum AS ENUM ('active','inactive','pending','banned'); END IF; END $$;");

                DB::statement("ALTER TABLE users ALTER COLUMN gender TYPE gender_enum USING gender::gender_enum;");
                DB::statement("ALTER TABLE users ALTER COLUMN status TYPE status_enum USING status::status_enum;");
            } catch (\Throwable $e) {
                // ignore failures
            }

            return;
        }

        // Fallback: try to add CHECK constraints (works on many DBs but may fail on SQLite)
        try {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_gender_check CHECK (gender IN ('male','female','non_binary','not_specified'))");
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active','inactive','pending','banned'))");
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            try {
                DB::statement("ALTER TABLE users MODIFY gender VARCHAR(255) NULL");
            } catch (\Throwable $e) {
            }

            try {
                DB::statement("ALTER TABLE users MODIFY status VARCHAR(255) NULL");
            } catch (\Throwable $e) {
            }

            return;
        }

        if ($driver === 'pgsql') {
            try {
                DB::statement("ALTER TABLE users ALTER COLUMN gender TYPE VARCHAR USING gender::text;");
                DB::statement("ALTER TABLE users ALTER COLUMN status TYPE VARCHAR USING status::text;");
                DB::statement("DROP TYPE IF EXISTS gender_enum");
                DB::statement("DROP TYPE IF EXISTS status_enum");
            } catch (\Throwable $e) {
            }

            return;
        }

        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_gender_check");
        } catch (\Throwable $e) {
        }

        try {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check");
        } catch (\Throwable $e) {
        }
    }
};
