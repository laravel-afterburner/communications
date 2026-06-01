<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_thread_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_thread_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('property_id');
            $table->timestamps();

            $table->unique(['discussion_thread_id', 'property_id'], 'dtp_thread_property_unique');
        });

        DB::table('discussion_threads')
            ->whereNotNull('property_id')
            ->orderBy('id')
            ->each(function (object $thread): void {
                DB::table('discussion_thread_property')->insert([
                    'discussion_thread_id' => $thread->id,
                    'property_id' => $thread->property_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('discussion_threads', function (Blueprint $table) {
            $table->dropColumn('property_id');
        });
    }

    public function down(): void
    {
        Schema::table('discussion_threads', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->after('scope');
        });

        $firstPropertyByThread = DB::table('discussion_thread_property')
            ->select('discussion_thread_id', DB::raw('MIN(property_id) as property_id'))
            ->groupBy('discussion_thread_id')
            ->get();

        foreach ($firstPropertyByThread as $row) {
            DB::table('discussion_threads')
                ->where('id', $row->discussion_thread_id)
                ->update(['property_id' => $row->property_id]);
        }

        Schema::dropIfExists('discussion_thread_property');
    }
};
