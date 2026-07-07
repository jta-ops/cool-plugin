<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('player_events')) {
            return;
        }

        Schema::table('player_events', function (Blueprint $table) {
            if (!Schema::hasColumn('player_events', 'source_hash')) {
                $table->string('source_hash', 64)->nullable()->after('event_at');
            }

            if (!Schema::hasColumn('player_events', 'source_path')) {
                $table->string('source_path')->nullable()->after('source_hash');
            }
        });

        Schema::table('player_events', function (Blueprint $table) {
            $table->unique(['server_id', 'source_hash'], 'player_events_server_source_hash_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('player_events')) {
            return;
        }

        Schema::table('player_events', function (Blueprint $table) {
            $table->dropUnique('player_events_server_source_hash_unique');

            if (Schema::hasColumn('player_events', 'source_path')) {
                $table->dropColumn('source_path');
            }

            if (Schema::hasColumn('player_events', 'source_hash')) {
                $table->dropColumn('source_hash');
            }
        });
    }
};
