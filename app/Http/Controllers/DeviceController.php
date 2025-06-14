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
            'code' => 'required'
        ]);

        $data = $request->all();
        $data['status'] = 'active';

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
            'status' => 'required|in:active,inactive'
        ]);

        $device->update($request->all());
        return response()->json($device);
    }

    public function updateStatus(Request $request, $id)
    {
        $device = Device::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        $device->update(['status' => $request->status]);
        return response()->json($device);
    }

    public function destroy($id)
    {
        $device = Device::findOrFail($id);
        $device->delete();
        return response()->json(null, 204);
    }
} 