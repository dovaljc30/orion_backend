<?php

namespace App\Http\Controllers;

use App\Models\Genotype;
use App\Models\Fermentation;
use Illuminate\Http\Request;

class GenotypeController extends Controller
{
    public function index()
    {
        $genotypes = Genotype::all();
        return response()->json($genotypes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:genotypes',
            'description' => 'nullable|string'
        ]);

        $genotype = Genotype::create($request->all());
        return response()->json($genotype, 201);
    }

    public function show($id)
    {
        $genotype = Genotype::findOrFail($id);
        return response()->json($genotype);
    }

    public function attachToFermentation(Request $request, $fermentationId)
    {
        $request->validate([
            'genotype_ids' => 'required|array',
            'genotype_ids.*' => 'exists:genotypes,id'
        ]);

        $fermentation = Fermentation::findOrFail($fermentationId);
        
        if ($fermentation->type !== 'Especial') {
            return response()->json([
                'message' => 'Solo las fermentaciones de tipo Especial pueden tener múltiples genotipos'
            ], 400);
        }

        $fermentation->specialGenotypes()->attach($request->genotype_ids);

        return response()->json([
            'message' => 'Genotipos asociados exitosamente',
            'fermentation' => $fermentation->load('specialGenotypes')
        ]);
    }

    public function detachFromFermentation(Request $request, $fermentationId)
    {
        $request->validate([
            'genotype_ids' => 'required|array',
            'genotype_ids.*' => 'exists:genotypes,id'
        ]);

        $fermentation = Fermentation::findOrFail($fermentationId);
        $fermentation->specialGenotypes()->detach($request->genotype_ids);

        return response()->json([
            'message' => 'Genotipos desasociados exitosamente',
            'fermentation' => $fermentation->load('specialGenotypes')
        ]);
    }
} 