<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Models\Device;
use App\Models\Sensor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeasurementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/measurements",
     *     summary="Obtener todas las mediciones",
     *     tags={"Measurements"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de mediciones"
     *     )
     * )
     */
    public function index()
    {
        $measurements = Measurement::with('sensor')->get();
        return response()->json($measurements);
    }

    /**
     * @OA\Post(
     *     path="/api/measurements",
     *     summary="Crear una nueva medición",
     *     tags={"Measurements"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sensor_id","date","data","measurement_type"},
     *             @OA\Property(property="sensor_id", type="integer", example=1),
     *             @OA\Property(property="date", type="string", format="date-time", example="2024-06-14T10:00:00"),
     *             @OA\Property(property="data", type="number", example=23.5),
     *             @OA\Property(property="measurement_type", type="string", example="temperatura"),
     *             example={"sensor_id":1,"date":"2024-06-14T10:00:00","data":23.5,"measurement_type":"temperatura"}
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Medición creada exitosamente"
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
            'serialNumber' => 'required|exists:devices,serial_number',
            'timestampEnvio' => 'required|date',
            'lecturas' => 'required|array',
            'lecturas.temperatura' => 'required|numeric',
            'lecturas.humedad_relativa' => 'required|numeric',
            'lecturas.humedad' => 'required|numeric',
            'lecturas.ph' => 'required|numeric',
            'lecturas.cov' => 'required|numeric',
            'lecturas.co2' => 'required|numeric',
            'lecturas.temperatura-cacao' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            // Obtener el dispositivo
            $device = Device::where('serial_number', $request->serialNumber)->firstOrFail();

            $measurementTypes = [
                'temperatura' => ['type' => 'ambient_temp', 'unit' => '°C'],
                'humedad_relativa' => ['type' => 'relative_humidity', 'unit' => '%'],
                'humedad' => ['type' => 'humidity', 'unit' => '%'],
                'ph' => ['type' => 'ph', 'unit' => 'pH'],
                'cov' => ['type' => 'cov', 'unit' => 'ppm'],
                'co2' => ['type' => 'co2', 'unit' => 'ppm'],
                'temperatura-cacao' => ['type' => 'cocoa_temp', 'unit' => '°C']
            ];

            $timestamp = Carbon::parse($request->timestampEnvio)->format('Y-m-d H:i:s');

            // Procesar cada lectura
            foreach ($request->lecturas as $type => $value) {
                // Obtener o crear el sensor
                $sensor = Sensor::firstOrCreate(
                    [
                        'type' => $measurementTypes[$type]['type'],
                        'device_id' => $device->id
                    ],
                    [
                        'name' => ucfirst($type)
                    ]
                );

                // Crear la medición
                Measurement::create([
                    'sensor_id' => $sensor->id,
                    'date' => $timestamp,
                    'data' => $value,
                    'measurement_type' => $measurementTypes[$type]['type'],
                    'unit' => $measurementTypes[$type]['unit']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Readings stored successfully',
                'device' => $device->serial_number,
                'timestamp' => $request->timestampEnvio
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error storing readings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/measurements/{id}",
     *     summary="Obtener una medición por ID",
     *     tags={"Measurements"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la medición",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Medición encontrada"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medición no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $measurement = Measurement::with('sensor')->findOrFail($id);
        return response()->json($measurement);
    }

    public function update(Request $request, $id)
    {
        $measurement = Measurement::findOrFail($id);
        
        $request->validate([
            'sensor_id' => 'required|exists:sensors,id',
            'date' => 'required|date',
            'data' => 'required|numeric',
            'measurement_type' => 'required|string|in:ph,conductivity,ambient_temp,relative_humidity,gases'
        ]);

        $measurement->update($request->all());
        return response()->json($measurement);
    }

    /**
     * @OA\Delete(
     *     path="/api/measurements/{id}",
     *     summary="Eliminar una medición por ID",
     *     tags={"Measurements"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la medición",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Medición eliminada"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Medición no encontrada"
     *     )
     * )
     */
    public function destroy($id)
    {
        $measurement = Measurement::findOrFail($id);
        $measurement->delete();
        return response()->json(null, 204);
    }
} 