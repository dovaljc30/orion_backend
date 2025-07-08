<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/sensors",
     *     summary="Obtener todos los sensores",
     *     tags={"Sensors"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de sensores"
     *     )
     * )
     */
    public function index()
    {
        $sensors = Sensor::with('measurements')->get();
        return response()->json($sensors);
    }

    /**
     * @OA\Post(
     *     path="/api/sensors",
     *     summary="Crear un nuevo sensor",
     *     tags={"Sensors"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id","type","name"},
     *             @OA\Property(property="device_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", example="temperatura"),
     *             @OA\Property(property="name", type="string", example="Sensor Temp 1"),
     *             example={"device_id":1,"type":"temperatura","name":"Sensor Temp 1"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Sensor creado exitosamente"
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
            'type' => 'required|string',
            'name' => 'required|string',
            'device_id' => 'required|exists:devices,id'
        ]);

        $sensor = Sensor::create([
            'type' => $request->type,
            'name' => $request->name,
            'device_id' => $request->device_id
        ]);

        return response()->json([
            'sensor' => $sensor->load('device'),
            'message' => 'Sensor creado y asignado exitosamente'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/sensors/{id}",
     *     summary="Obtener un sensor por ID",
     *     tags={"Sensors"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del sensor",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sensor encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sensor no encontrado"
     *     )
     * )
     */
    public function show($id)
    {
        $sensor = Sensor::with('measurements')->findOrFail($id);
        return response()->json($sensor);
    }

    public function update(Request $request, $id)
    {
        $sensor = Sensor::findOrFail($id);
        
        $request->validate([
            'type' => 'required|string',
            'name' => 'required|string',
            'device_id' => 'required|exists:devices,id'
        ]);

        $sensor->update([
            'type' => $request->type,
            'name' => $request->name,
            'device_id' => $request->device_id
        ]);

        return response()->json([
            'sensor' => $sensor->load('device'),
            'message' => 'Sensor actualizado exitosamente'
        ]);
    }

    public function destroy($id)
    {
        $sensor = Sensor::findOrFail($id);
        $sensor->delete();
        return response()->json(null, 204);
    }
} 