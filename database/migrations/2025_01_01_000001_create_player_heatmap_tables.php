<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('player_heatmap_samples')) {
            Schema::create('player_heatmap_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_of_week')->comment('0=Mon, 6=Sun');
            $table->unsignedTinyInteger('hour')->comment('0-23');
            $table->unsignedInteger('player_count')->default(0);
            $table->unsignedInteger('sample_count')->default(0);
            $table->timestamps();

            $table->unique(['server_id', 'day_of_week', 'hour']);
            $table->index(['server_id', 'day_of_week']);
            $table->index('server_id');
            });
        }

        if (!Schema::hasTable('player_events')) {
            Schema::create('player_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('player_name');
            $table->enum('event', ['join', 'leave']);
            $table->timestamp('event_at');
            $table->string('source_hash', 64)->nullable();
            $table->string('source_path')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'event_at']);
            $table->index(['server_id', 'player_name', 'event_at']);
            $table->unique(['server_id', 'source_hash']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('player_events');
        Schema::dropIfExists('player_heatmap_samples');
    }
};
