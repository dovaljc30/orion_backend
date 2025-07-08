<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

/**
 * @OA\Get(
 *     path="/api/devices",
 *     summary="Obtener todos los dispositivos",
 *     tags={"Devices"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de dispositivos"
 *     )
 * )
 */
class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::with('fermentations')->get();
        return response()->json($devices);
    }

    /**
     * @OA\Post(
     *     path="/api/devices",
     *     summary="Crear un nuevo dispositivo",
     *     tags={"Devices"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"serial_number","code","status"},
     *             @OA\Property(property="serial_number", type="string", example="SN20240614A"),
     *             @OA\Property(property="code", type="string", example="DEV-1001"),
     *             @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Dispositivo creado exitosamente"
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
            'serial_number' => 'required|unique:devices',
            'code' => 'required',
            'status' => 'required|in:1,0'
        ]);

        $data = $request->all();
       

        $device = Device::create($data);
        return response()->json($device, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/devices/{id}",
     *     summary="Obtener un dispositivo por ID",
     *     tags={"Devices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del dispositivo",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dispositivo encontrado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Dispositivo no encontrado"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/devices/{id}/status",
     *     summary="Actualizar el estado de un dispositivo",
     *     tags={"Devices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del dispositivo",
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
     *         description="Dispositivo no encontrado"
     *     )
     * )
     */
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