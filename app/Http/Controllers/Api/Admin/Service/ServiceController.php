<?php

namespace App\Http\Controllers\Api\Admin\Service;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Get all services
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Services retrieved successfully.',
            'data' => Service::all()
        ], 200);

    }

    // Get a single service by ID
    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'The requested service could not be found.'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Service details retrieved successfully.',
            'data' => $service
        ], 200);

    }

    // Create a new service
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $service = Service::create($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'service' => $service
        ], 201);

    }

    // Update an existing service
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $service->update($request->all());
        return response()->json([
            'success' => true,
            'message' => 'Service retrieved successfully.',
            'service' => $service
        ], 200);

    }

    // Delete a service
    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);

        }

        $service->delete();
        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ], 200);

    }
}

