<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instruction;
use App\Enums\InstructionModule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InstructionController extends Controller
{
    /**
     * List all instructions
     */
    public function index()
    {
        $instructions = Instruction::latest()->get();

        return response()->json([
            'status' => true,
            'data'   => $instructions,
        ]);
    }

    public function list()
    {
        $instructions = Instruction::select('id', 'title', 'description', 'image')
            ->where('is_active', true)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $instructions,
        ]);
    }

    /**
     * Store instruction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => 'required|mimes:jpeg,png,jpg,gif,svg,webp',
            'module'      => 'nullable|string|in:' . implode(',', InstructionModule::values()),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }


        $data = $request->except('image');
        $data['created_by'] = auth('admin-api')->id();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('image')) {
            $directory = 'instructions/images';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $data['image'] = $request->file('image')->store($directory, 'public');
        }

        $instruction = Instruction::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Instruction created successfully',
            'data'    => $instruction,
        ], 201);
    }

    /**
     * Show instruction
     */
    public function show($id)
    {
        $instruction = Instruction::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $instruction,
        ]);
    }

    /**
     * Update instruction
     */
    public function update(Request $request, $id)
    {
        // dd("Awdawd");
        
        $instruction = Instruction::find($id);
        if (!$instruction) {
            return response()->json([
                'status'  => false,
                'message' => 'Instruction not found',
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp',
            'module'      => 'nullable|string|in:' . implode(',', InstructionModule::values()),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($instruction->image) {
                Storage::disk('public')->delete($instruction->image);
            }
            $directory = 'instructions/images';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $data['image'] = $request->file('image')->store($directory, 'public');
        }
        $instruction->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Instruction updated successfully',
            'data'    => $instruction,
        ]);
    }

    /**
     * Delete instruction
     */
    public function destroy($id)
    {
        $instruction = Instruction::findOrFail($id);

        // Delete the image from storage
        if ($instruction->image) {
            Storage::disk('public')->delete($instruction->image);
        }
        $instruction->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Instruction deleted successfully',
        ]);
    }
}
