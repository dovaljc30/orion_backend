<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with('fermentations')->get();
        return response()->json($devices);
    }

    public function store(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|unique:devices',
            'code' => 'required',
            'status' => 'nullable|in:1,0'
        ]);

        $data = $request->all();
        $data['status'] = $request->status ?? 1;

        $device = Device::create($data);
        return response()->json($device, 201);
    }

    public function show($id)
    {
        $device = Device::with('fermentations')->findOrFail($id);
        return response()->json($device);
    }

    public function update(Request $request, $id)
    {
        $device = Device::findOrFail($id);
        
        $request->validate([
            'serial_number' => 'required|unique:devices,serial_number,' . $id,
            'code' => 'required',
            'status' => 'nullable|in:1,0'
        ]);

        $data = $request->all();
        if (!isset($data['status'])) {
            $data['status'] = 1;
        }

        $device->update($data);
        return response()->json($device);
    }

    public function updateStatus(Request $request, $id)
    {
        $device = Device::findOrFail($id);
        
        $request->validate([
            'status' => 'nullable|in:1,0'
        ]);

        $device->update(['status' => $request->status ?? 1]);
        return response()->json($device);
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();
        return response()->json(null, 204);
    }
} 