<?php

namespace App\Http\Controllers;

use App\Models\Turn;
use Illuminate\Http\Request;

class TurnController extends Controller
{
    public function index()
    {
        $turns = Turn::with('fermentation')->get();
        return response()->json($turns);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fermentation_id' => 'required|exists:fermentations,id',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date',
            'status' => 'nullable|string'
        ]);

        $data = $request->all();
        $data['status'] = $request->status ?? 'active';

        $turn = Turn::create($data);
        return response()->json($turn, 201);
    }

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
            'status' => 'nullable|string'
        ]);

        $data = $request->all();
        if (!isset($data['status'])) {
            $data['status'] = 'active';
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