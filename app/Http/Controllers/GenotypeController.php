<?php

namespace App\Http\Controllers;

use App\Models\Genotype;
use App\Models\Fermentation;
use Illuminate\Http\Request;

class GenotypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/genotypes",
     *     summary="Obtener todos los genotipos",
     *     tags={"Genotypes"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de genotipos"
     *     )
     * )
     */
    public function index()
    {
        $genotypes = Genotype::all();
        return response()->json($genotypes);
    }

    /**
     * @OA\Post(
     *     path="/api/genotypes",
     *     summary="Crear un nuevo genotipo",
     *     tags={"Genotypes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","code"},
     *             @OA\Property(property="name", type="string", example="CCN-51"),
     *             @OA\Property(property="code", type="string", example="GEN-001"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Genotipo de cacao resistente a enfermedades"),
     *             example={"name":"CCN-51","code":"GEN-001","description":"Genotipo de cacao resistente a enfermedades"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Genotipo creado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/genotypes/{id}",
     *     summary="Obtener un genotipo por ID",
     *     tags={"Genotypes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del genotipo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Genotipo encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Genotipo no encontrado"
     *     )
     * )
     */
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