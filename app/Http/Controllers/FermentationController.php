<?php

namespace App\Http\Controllers;

use App\Models\Fermentation;
use App\Models\Measurement;
use App\Models\Genotype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FermentationController extends Controller
{
    public function index()
    {
        $fermentations = Fermentation::select('id', 'device_id', 'start_time', 'end_time', 'status', 'title', 'type', 'note')
            ->with(['genotypes' => function($query) {
                $query->select('genotypes.id', 'name')
                    ->withPivot('quantity');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($fermentations);
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|exists:devices,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable|string|in:active,paused,completed,cancelled,inactive',
            'type' => 'required|in:Especial,Premium',
            'note' => 'nullable|string',
            'genotypes' => 'required|array',
            'genotypes.*.id' => 'required|exists:genotypes,id',
            'genotypes.*.quantity' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['status'] = $request->status ?? 'active';

            // Verificar si el dispositivo ya tiene una fermentación activa
            $activeFermentation = Fermentation::where('device_id', $request->device_id)
                ->where('status', 'active')
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
            'status' => 'required|string',
            'type' => 'required|in:Especial,Premium',
            'note' => 'nullable|string',
            'genotypes' => 'required|array',
            'genotypes.*.id' => 'required|exists:genotypes,id',
            'genotypes.*.quantity' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();

            // Verificar si el dispositivo ya tiene una fermentación activa (excluyendo la actual)
            if ($data['status'] === 'active') {
                $activeFermentation = Fermentation::where('device_id', $request->device_id)
                    ->where('status', 'active')
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
        $fermentations = Fermentation::select('id', 'device_id', 'start_time', 'end_time', 'status', 'title', 'type', 'note')
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

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:active,paused,completed,cancelled,inactive'
        ]);

        $fermentation = Fermentation::findOrFail($id);
        $fermentation->status = $request->status;
        
        // Si el estado es 'completed', actualizamos end_time
        if ($request->status === 'completed') {
            $fermentation->end_time = now();
        }
        
        $fermentation->save();

        return response()->json([
            'message' => 'Estado de fermentación actualizado exitosamente',
            'fermentation' => $fermentation
        ]);
    }
} 