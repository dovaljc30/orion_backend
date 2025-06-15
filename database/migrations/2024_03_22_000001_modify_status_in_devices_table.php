<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Primero convertimos los valores existentes
            DB::statement("UPDATE devices SET status = CASE WHEN status = 'active' THEN 1 ELSE 0 END");
            
            // Luego modificamos la columna
            $table->tinyInteger('status')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Primero convertimos los valores existentes
            DB::statement("UPDATE devices SET status = CASE WHEN status = 1 THEN 'active' ELSE 'inactive' END");
            
            // Luego modificamos la columna
            $table->string('status')->default('active')->change();
        });
    }
}; 