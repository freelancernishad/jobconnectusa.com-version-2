<?php

use App\Models\User;
use Illuminate\Http\Request;

function createRelatedModels(User $user, Request $request)
{
    // Update or create languages
    if ($request->has('languages')) {
        $user->languages()->delete(); // Delete existing languages
        $user->languages()->createMany($request->languages); // Create new languages
    }

    // Update or create certifications
    if ($request->has('certifications')) {
        $user->certifications()->delete(); // Delete existing certifications
        $user->certifications()->createMany($request->certifications); // Create new certifications
    }

    // Update or create skills
    if ($request->has('skills')) {
        $user->skills()->delete(); // Delete existing skills
        $user->skills()->createMany($request->skills); // Create new skills
    }

    // Update or create education
    if ($request->has('education')) {
        $user->education()->delete(); // Delete existing education records
        $user->education()->createMany($request->education); // Create new education records
    }

    // Update or create employment history
    if ($request->has('employment_history')) {
        $user->employmentHistory()->delete(); // Delete existing employment history
        $user->employmentHistory()->createMany($request->employment_history); // Create new employment history
    }

    // Update or create looking services
    if ($request->has('looking_services') || $request->has('other_looking_services')) {
        $user->lookingServices()->delete(); // Delete existing looking services

        if ($request->has('looking_services')) {
            $user->lookingServices()->createMany(
                array_map(fn($id) => ['service_id' => $id], $request->looking_services)
            );
        }

        if ($request->has('other_looking_services')) {
            $user->lookingServices()->createMany(
                array_map(fn($title) => ['service_title' => $title], $request->other_looking_services)
            );
        }
    }
}
