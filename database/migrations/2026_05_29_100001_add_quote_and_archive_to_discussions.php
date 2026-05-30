<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussion_threads', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('locked_at');
            $table->index(['team_id', 'archived_at']);
        });

        Schema::table('discussion_posts', function (Blueprint $table) {
            $table->foreignId('quoted_post_id')
                ->nullable()
                ->after('body')
                ->constrained('discussion_posts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discussion_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quoted_post_id');
        });

        Schema::table('discussion_threads', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
