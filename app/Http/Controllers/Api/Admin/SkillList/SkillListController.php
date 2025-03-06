<?php

namespace App\Http\Controllers\Api\Admin\SkillList;

use App\Http\Controllers\Controller;
use App\Models\SkillList;
use Illuminate\Http\Request;

class SkillListController extends Controller
{
    // Get all skill lists
    public function index()
    {
        $skillLists = SkillList::all();
        return response()->json([
            'success' => true,
            'message' => 'Retrieved all skill lists successfully.',
            'data' => $skillLists
        ]);
    }

    // Get a single skill list by ID
    public function show($id)
    {
        $skillList = SkillList::find($id);
        if (!$skillList) {
            return response()->json([
                'success' => false,
                'message' => 'SkillList not found.'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'SkillList retrieved successfully.',
            'data' => $skillList
        ]);
    }

    // Create a new skill list
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
        ]);

        $skillList = SkillList::create($request->all());
        return response()->json([
            'success' => true,
            'message' => 'SkillList created successfully.',
            'data' => $skillList
        ], 201);
    }

    // Update an existing skill list
    public function update(Request $request, $id)
    {
        $skillList = SkillList::find($id);
        if (!$skillList) {
            return response()->json([
                'success' => false,
                'message' => 'SkillList not found.'
            ], 404);
        }

        $request->validate([
            'service_id' => 'sometimes|required|exists:services,id',
            'name' => 'sometimes|required|string|max:255',
        ]);

        $skillList->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'SkillList updated successfully.',
            'data' => $skillList
        ]);
    }

    // Delete a skill list
    public function destroy($id)
    {
        $skillList = SkillList::find($id);
        if (!$skillList) {
            return response()->json([
                'success' => false,
                'message' => 'SkillList not found.'
            ], 404);
        }

        $skillList->delete();
        return response()->json([
            'success' => true,
            'message' => 'SkillList deleted successfully.'
        ]);
    }

    // List skill lists by service ID
    public function listByService($serviceId)
    {
        $skillLists = SkillList::where('service_id', $serviceId)->get();
        return response()->json([
            'success' => true,
            'message' => 'SkillLists retrieved successfully for the given service.',
            'data' => $skillLists
        ]);
    }
}
