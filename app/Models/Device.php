<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $table = 'devices';

    protected $fillable = [
        'serial_number',
        'code',
        'status'
    ];

    public function fermentations()
    {
        return $this->hasMany(Fermentation::class, 'device_id', 'id');
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }
} 