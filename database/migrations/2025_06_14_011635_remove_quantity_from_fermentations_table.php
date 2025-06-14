<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fermentations', function (Blueprint $table) {
            if (Schema::hasColumn('fermentations', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fermentations', function (Blueprint $table) {
            if (!Schema::hasColumn('fermentations', 'quantity')) {
                $table->decimal('quantity', 10, 2)->nullable();
            }
        });
    }
};
