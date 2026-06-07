<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 8);
            $table->timestamps();

            $table->unique(['discussion_post_id', 'user_id']);
            $table->index(['discussion_post_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_post_reactions');
    }
};
