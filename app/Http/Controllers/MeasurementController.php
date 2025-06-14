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
    public function index()
    {
        $measurements = Measurement::with('sensor')->get();
        return response()->json($measurements);
    }

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

    public function destroy($id)
    {
        $measurement = Measurement::findOrFail($id);
        $measurement->delete();
        return response()->json(null, 204);
    }
} 