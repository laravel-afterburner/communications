<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussion_posts', function (Blueprint $table) {
            $table->text('quoted_post_body')->nullable()->after('quoted_post_id');
            $table->string('quoted_post_author_name')->nullable()->after('quoted_post_body');
            $table->timestamp('quoted_post_created_at')->nullable()->after('quoted_post_author_name');
        });
    }

    public function down(): void
    {
        Schema::table('discussion_posts', function (Blueprint $table) {
            $table->dropColumn([
                'quoted_post_body',
                'quoted_post_author_name',
                'quoted_post_created_at',
            ]);
        });
    }
};
