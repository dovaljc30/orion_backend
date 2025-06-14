<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Primero verificamos si la tabla existe
        if (!Schema::hasTable('fermentations')) {
            Schema::create('fermentations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('device_id')->constrained('devices');
                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();
                $table->string('status')->default('active');
                $table->string('title')->unique();
                $table->enum('type', ['Especial', 'Premium']);
                $table->text('note')->nullable();
                $table->decimal('quantity', 10, 2);
                $table->timestamps();
            });
        } else {
            // Si la tabla existe, verificamos y agregamos las columnas necesarias
            Schema::table('fermentations', function (Blueprint $table) {
                if (!Schema::hasColumn('fermentations', 'device_id')) {
                    $table->foreignId('device_id')->constrained('devices');
                }
                if (!Schema::hasColumn('fermentations', 'start_time')) {
                    $table->dateTime('start_time');
                }
                if (!Schema::hasColumn('fermentations', 'end_time')) {
                    $table->dateTime('end_time')->nullable();
                }
                if (!Schema::hasColumn('fermentations', 'status')) {
                    $table->string('status')->default('active');
                }
                if (!Schema::hasColumn('fermentations', 'title')) {
                    $table->string('title')->unique();
                }
                if (!Schema::hasColumn('fermentations', 'type')) {
                    $table->enum('type', ['Especial', 'Premium']);
                }
                if (!Schema::hasColumn('fermentations', 'note')) {
                    $table->text('note')->nullable();
                }
                if (!Schema::hasColumn('fermentations', 'quantity')) {
                    $table->decimal('quantity', 10, 2);
                }
            });
        }
    }

    public function down()
    {
        // No hacemos nada en el down ya que queremos mantener la estructura
    }
}; 