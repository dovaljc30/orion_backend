<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('fermentation_genotype')) {
            Schema::create('fermentation_genotype', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fermentation_id')->constrained()->onDelete('cascade');
                $table->foreignId('genotype_id')->constrained()->onDelete('cascade');
                $table->decimal('quantity', 10, 2);
                $table->timestamps();

                // Aseguramos que no se pueda duplicar la combinación de fermentación y genotipo
                $table->unique(['fermentation_id', 'genotype_id']);
            });
        } else {
            // Si la tabla existe, verificamos y agregamos las columnas necesarias
            Schema::table('fermentation_genotype', function (Blueprint $table) {
                if (!Schema::hasColumn('fermentation_genotype', 'quantity')) {
                    $table->decimal('quantity', 10, 2);
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('fermentation_genotype');
    }
}; 