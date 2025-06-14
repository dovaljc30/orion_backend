<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fermentations', function (Blueprint $table) {
            if (Schema::hasColumn('fermentations', 'genotype_id')) {
                $table->dropForeign(['genotype_id']);
                $table->dropColumn('genotype_id');
            }
        });
    }

    public function down()
    {
        Schema::table('fermentations', function (Blueprint $table) {
            if (!Schema::hasColumn('fermentations', 'genotype_id')) {
                $table->foreignId('genotype_id')->nullable()->constrained('genotypes');
            }
        });
    }
}; 