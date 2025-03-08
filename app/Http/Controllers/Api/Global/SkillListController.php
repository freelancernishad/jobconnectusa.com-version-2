<?php

namespace App\Http\Controllers\Api\Global;

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
            'message' => 'Successfully retrieved the list of all skill lists.',
            'data' => $skillLists
        ], 200);
    }

    // Get a single skill list by ID
    public function show($id)
    {
        $skillList = SkillList::find($id);

        if (!$skillList) {
            return response()->json([
                'success' => false,
                'message' => 'SkillList not found. Please check the ID and try again.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully retrieved the skill list details.',
            'data' => $skillList
        ], 200);
    }

    // List skill lists by service ID
    public function listByService($serviceId)
    {
        $skillLists = SkillList::where('service_id', $serviceId)->get();

        return response()->json([
            'success' => true,
            'message' => 'Successfully retrieved the skill lists for the specified service.',
            'data' => $skillLists
        ], 200);
    }
}
