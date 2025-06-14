<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turn extends Model
{
    use HasFactory;

    protected $fillable = [
        'fermentation_id',
        'start_time',
        'end_time',
        'status'
    ];

    public function fermentation()
    {
        return $this->belongsTo(Fermentation::class, 'fermentation_id', 'id');
    }
} 