<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function index()
    {
        $sensors = Sensor::with('measurements')->get();
        return response()->json($sensors);
    }

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