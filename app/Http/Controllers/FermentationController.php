<?php

namespace App\Http\Controllers;

use App\Models\Fermentation;
use App\Models\Measurement;
use App\Models\Genotype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Get(
 *     path="/api/fermentations",
 *     summary="Obtener todas las fermentaciones",
 *     tags={"Fermentations"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de fermentaciones"
 *     )
 * )
 */
class FermentationController extends Controller
{
    public function index()
    {
        $fermentations = Fermentation::select('id', 'device_id', 'start_time', 'end_time', 'status', 'title', 'type', 'note', 'code')
            ->with([
                'genotypes' => function($query) {
                    $query->select('genotypes.id', 'name')
                        ->withPivot('quantity');
                },
                'device'
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($fermentations);
    }

    /**
     * @OA\Post(
     *     path="/api/fermentations",
     *     summary="Crear una nueva fermentación",
     *     tags={"Fermentations"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"device_id","start_time","status","type","genotypes"},
     *             @OA\Property(property="device_id", type="integer", example=1),
     *             @OA\Property(property="start_time", type="string", format="date-time", example="2024-06-14T10:00:00"),
     *             @OA\Property(property="end_time", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1),
     *             @OA\Property(property="type", type="string", enum={"Especial","Premium"}, example="Premium"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Nota de prueba"),
     *             @OA\Property(
     *                 property="genotypes",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="number", example=10)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fermentación creada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación o lógica de negocio"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'required|in:1,0',
            'type' => 'required|in:Especial,Premium',
            'note' => 'nullable|string',
            'code' => 'nullable|string|unique:fermentations,code',
            'genotypes' => 'required|array',
            'genotypes.*.id' => 'required|exists:genotypes,id',
            'genotypes.*.quantity' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
           

            // Verificar si el dispositivo ya tiene una fermentación activa
            $activeFermentation = Fermentation::where('device_id', $request->device_id)
                ->where('status', 1)
                ->first();

            if ($activeFermentation) {
                return response()->json([
                    'message' => 'El dispositivo ya tiene una fermentación activa'
                ], 400);
            }

            // Validar según el tipo de fermentación
            if ($data['type'] === 'Premium' && count($request->genotypes) !== 1) {
                return response()->json([
                    'message' => 'Las fermentaciones Premium deben tener exactamente un genotipo'
                ], 400);
            }

            if ($data['type'] === 'Especial' && count($request->genotypes) < 1) {
                return response()->json([
                    'message' => 'Las fermentaciones Especiales deben tener al menos un genotipo'
                ], 400);
            }

            $fermentation = Fermentation::create($data);

            // Preparar los datos para la tabla pivot
            $genotypesWithQuantity = collect($request->genotypes)->mapWithKeys(function ($genotype) {
                return [$genotype['id'] => ['quantity' => $genotype['quantity']]];
            })->toArray();

            $fermentation->genotypes()->attach($genotypesWithQuantity);

            DB::commit();

            return response()->json([
                'message' => 'Fermentación creada exitosamente',
                'fermentation' => $fermentation->load(['genotypes' => function($query) {
                    $query->select('genotypes.id', 'name')
                        ->withPivot('quantity');
                }])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear la fermentación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/fermentations/{id}",
     *     summary="Obtener una fermentación por ID",
     *     tags={"Fermentations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la fermentación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fermentación encontrada"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fermentación no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $fermentation = Fermentation::with(['device', 'turns', 'genotypes'])->findOrFail($id);
        return response()->json($fermentation);
    }

    public function update(Request $request, $id)
    {
        $fermentation = Fermentation::findOrFail($id);
        
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable|in:1,0',
            'type' => 'required|in:Especial,Premium',
            'note' => 'nullable|string',
            'code' => 'nullable|string|unique:fermentations,code,' . $id,
            'genotypes' => 'required|array',
            'genotypes.*.id' => 'required|exists:genotypes,id',
            'genotypes.*.quantity' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
            if (!isset($data['status'])) {
                $data['status'] = 1;
            }

            // Verificar si el dispositivo ya tiene una fermentación activa (excluyendo la actual)
            if ($data['status'] === 1) {
                $activeFermentation = Fermentation::where('device_id', $request->device_id)
                    ->where('status', 1)
                    ->where('id', '!=', $id)
                    ->first();

                if ($activeFermentation) {
                    return response()->json([
                        'message' => 'El dispositivo ya tiene otra fermentación activa'
                    ], 400);
                }
            }

            // Validar según el tipo de fermentación
            if ($data['type'] === 'Premium' && count($request->genotypes) !== 1) {
                return response()->json([
                    'message' => 'Las fermentaciones Premium deben tener exactamente un genotipo'
                ], 400);
            }

            if ($data['type'] === 'Especial' && count($request->genotypes) < 1) {
                return response()->json([
                    'message' => 'Las fermentaciones Especiales deben tener al menos un genotipo'
                ], 400);
            }

            $fermentation->update($data);

            // Preparar los datos para la tabla pivot
            $genotypesWithQuantity = collect($request->genotypes)->mapWithKeys(function ($genotype) {
                return [$genotype['id'] => ['quantity' => $genotype['quantity']]];
            })->toArray();

            $fermentation->genotypes()->sync($genotypesWithQuantity);

            DB::commit();

            return response()->json([
                'message' => 'Fermentación actualizada exitosamente',
                'fermentation' => $fermentation->load(['genotypes' => function($query) {
                    $query->select('genotypes.id', 'name')
                        ->withPivot('quantity');
                }])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar la fermentación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $fermentation = Fermentation::findOrFail($id);
        $fermentation->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/fermentations/{id}/measurements",
     *     summary="Obtener las mediciones de una fermentación",
     *     tags={"Fermentations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la fermentación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mediciones encontradas"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fermentación no encontrada"
     *     )
     * )
     */
    public function getMeasurementsByFermentation($id)
    {
        $measurements = Measurement::join('sensors', 'measurements.sensor_id', '=', 'sensors.id')
            ->join('devices', 'sensors.device_id', '=', 'devices.id')
            ->join('fermentations', 'devices.id', '=', 'fermentations.device_id')
            ->where('fermentations.id', $id)
            ->select('measurements.date', 'measurements.data', 'measurements.measurement_type')
            ->orderBy('measurements.date', 'desc')
            ->get()
            ->groupBy('date')
            ->first();

        $result = ['date' => $measurements->first()->date];
        
        foreach ($measurements as $measurement) {
            $result[$measurement->measurement_type] = $measurement->data;
        }

        return response()->json([$result]);
    }

    public function getAllFermentations()
    {
        $fermentations = Fermentation::select('id', 'device_id', 'start_time', 'end_time', 'status', 'title', 'type', 'note', 'code')
            ->with('device:id,name')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($fermentation) {
                return [
                    'id' => $fermentation->id,
                    'device' => $fermentation->device->name,
                    'start_time' => $fermentation->start_time,
                    'end_time' => $fermentation->end_time,
                    'status' => $fermentation->status,
                    'title' => $fermentation->title,
                    'type' => $fermentation->type,
                    'note' => $fermentation->note,
                    'code' => $fermentation->code,
                    'total_quantity' => $fermentation->total_quantity
                ];
            });

        return response()->json($fermentations);
    }

    private function getLastMeasurement($fermentationId)
    {
        $measurements = Measurement::join('sensors', 'measurements.sensor_id', '=', 'sensors.id')
            ->join('devices', 'sensors.device_id', '=', 'devices.id')
            ->join('fermentations', 'devices.id', '=', 'fermentations.device_id')
            ->where('fermentations.id', $fermentationId)
            ->select('measurements.date', 'measurements.data', 'measurements.measurement_type')
            ->orderBy('measurements.date', 'desc')
            ->get()
            ->groupBy('date')
            ->first();

        if (!$measurements) {
            return null;
        }

        $result = ['date' => $measurements->first()->date];
        
        foreach ($measurements as $measurement) {
            $result[$measurement->measurement_type] = $measurement->data;
        }

        return $result;
    }

    /**
     * @OA\Get(
     *     path="/api/fermentations/{id}/measurements/{limit}",
     *     summary="Obtener las últimas mediciones de una fermentación con límite",
     *     tags={"Fermentations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la fermentación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="path",
     *         required=true,
     *         description="Cantidad máxima de registros a devolver",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mediciones encontradas"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fermentación no encontrada"
     *     )
     * )
     */
    public function getLastMeasurementsLimit($fermentationId, $limit)
    {
        $measurements = Measurement::join('sensors', 'measurements.sensor_id', '=', 'sensors.id')
            ->join('devices', 'sensors.device_id', '=', 'devices.id')
            ->join('fermentations', 'devices.id', '=', 'fermentations.device_id')
            ->where('fermentations.id', $fermentationId)
            ->select('measurements.date', 'measurements.data', 'measurements.measurement_type')
            ->orderBy('measurements.date', 'desc')
            ->get()
            ->groupBy('date')
            ->take($limit);

        if ($measurements->isEmpty()) {
            return null;
        }

        $result = [];
        
        foreach ($measurements as $date => $measurementGroup) {
            $dateResult = ['date' => $date];
            foreach ($measurementGroup as $measurement) {
                $dateResult[$measurement->measurement_type] = $measurement->data;
            }
            $result[] = $dateResult;
        }

        return $result;
    }

    /**
     * @OA\Put(
     *     path="/api/fermentations/{id}/status",
     *     summary="Actualizar el estado de una fermentación",
     *     tags={"Fermentations"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la fermentación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado actualizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Fermentación no encontrada"
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'nullable|in:1,0'
        ]);

        $fermentation = Fermentation::findOrFail($id);
        $fermentation->status = $request->status ?? 1;
        
        // Si el estado es 0 (inactivo), actualizamos end_time
        if ($fermentation->status === 0) {
            $fermentation->end_time = now();
        }
        
        $fermentation->save();

        return response()->json([
            'message' => 'Estado de fermentación actualizado exitosamente',
            'fermentation' => $fermentation
        ]);
    }
} 