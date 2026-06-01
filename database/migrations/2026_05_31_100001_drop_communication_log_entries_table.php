<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('communication_log_entries');
    }

    public function down(): void
    {
        // Communication log was removed from the package; table is not recreated.
    }
};
