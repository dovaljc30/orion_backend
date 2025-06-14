<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $table = 'sensors';

    protected $fillable = [
        'type',
        'name',
        'device_id'
    ];

    public function measurements()
    {
        return $this->hasMany(Measurement::class, 'sensor_id', 'id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
} 