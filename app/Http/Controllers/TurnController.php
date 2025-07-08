<?php

namespace App\Http\Controllers;

use App\Models\Turn;
use Illuminate\Http\Request;

class TurnController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/turns",
     *     summary="Obtener todos los turnos",
     *     tags={"Turns"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de turnos"
     *     )
     * )
     */
    public function index()
    {
        $turns = Turn::with('fermentation')->get();
        return response()->json($turns);
    }

    /**
     * @OA\Post(
     *     path="/api/turns",
     *     summary="Crear un nuevo turno",
     *     tags={"Turns"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fermentation_id","start_time"},
     *             @OA\Property(property="fermentation_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2024-06-14T10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", nullable=true, example=null),
     *             example={"fermentation_id":1,"start_time":"2024-06-14T10:00:00","end_time":null}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Turno creado exitosamente"
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
            'fermentation_id' => 'required|exists:fermentations,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable|in:1,0'
        ]);

        $data = $request->all();
        $data['status'] = $request->status ?? 1;

        $turn = Turn::create($data);
        return response()->json($turn, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/turns/{id}",
     *     summary="Obtener un turno por ID",
     *     tags={"Turns"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del turno",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Turno encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Turno no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $turn = Turn::with('fermentation')->findOrFail($id);
        return response()->json($turn);
    }

    public function update(Request $request, $id)
    {
        $turn = Turn::findOrFail($id);
        
        $request->validate([
            'fermentation_id' => 'required|exists:fermentations,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable|in:1,0'
        ]);

        $data = $request->all();
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }

        $turn->update($data);
        return response()->json($turn);
    }

    public function destroy($id)
    {
        $turn = Turn::findOrFail($id);
        $turn->delete();
        return response()->json(null, 204);
    }
} 