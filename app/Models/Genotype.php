<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genotype extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description'
    ];

    public function premiumFermentations()
    {
        return $this->hasMany(Fermentation::class, 'genotype_id');
    }

    public function specialFermentations()
    {
        return $this->belongsToMany(Fermentation::class, 'fermentation_genotype');
    }
} 