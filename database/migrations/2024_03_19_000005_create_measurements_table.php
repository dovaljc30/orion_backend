<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('sensors');
            $table->dateTime('date');
            $table->float('data');
            $table->string('measurement_type')->comment('ph, conductivity, ambient_temp, relative_humidity, gases');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
}; 