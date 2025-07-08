<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fermentation extends Model
{
    use HasFactory;

    protected $table = 'fermentations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'device_id',
        'start_time',
        'end_time',
        'status',
        'title',
        'type',
        'note',
        'code', 
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($fermentation) {
            if (empty($fermentation->title)) {
                $year = date('Y');
                $lastTitle = static::where('title', 'like', $year . '-%')
                    ->orderBy('title', 'desc')
                    ->first();
                
                if ($lastTitle) {
                    $lastNumber = (int) substr($lastTitle->title, 5);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $fermentation->title = $year . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'id');
    }

    public function turns()
    {
        return $this->hasMany(Turn::class, 'fermentation_id', 'id');
    }

    public function getMeasurements()
    {
        return $this->hasMany(Measurement::class, 'fermentation_id', 'id');
    }

    public function genotypes()
    {
        return $this->belongsToMany(Genotype::class, 'fermentation_genotype')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function addGenotype($genotypeId, $quantity)
    {
        $result = $this->genotypes()->attach($genotypeId, ['quantity' => $quantity]);
        $this->refresh();
        return $result;
    }

    public function updateGenotypeQuantity($genotypeId, $quantity)
    {
        $result = $this->genotypes()->updateExistingPivot($genotypeId, ['quantity' => $quantity]);
        $this->refresh();
        return $result;
    }

    public function removeGenotype($genotypeId)
    {
        $result = $this->genotypes()->detach($genotypeId);
        $this->refresh();
        return $result;
    }

    public function getTotalQuantityAttribute()
    {
        return $this->genotypes()->sum('quantity');
    }

    public function syncGenotypes($genotypes)
    {
        if ($this->type === 'Premium' && count($genotypes) === 1) {
            $quantity = $genotypes[0]['quantity'];
            $genotypesWithQuantity = collect($genotypes)->mapWithKeys(function ($genotype) use ($quantity) {
                return [$genotype['id'] => ['quantity' => $quantity]];
            })->toArray();
            
            return $this->genotypes()->sync($genotypesWithQuantity);
        } else {
            $genotypesWithQuantity = collect($genotypes)->mapWithKeys(function ($genotype) {
                return [$genotype['id'] => ['quantity' => $genotype['quantity']]];
            })->toArray();
            
            return $this->genotypes()->sync($genotypesWithQuantity);
        }
    }
} 