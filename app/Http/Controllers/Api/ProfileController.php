<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * INSIGHT & KNOWLEDGE:
     * Method index() bertugas mengembalikan data profil pertama dalam format JSON.
     * Karena website portofolio biasanya hanya memiliki satu entitas data profil utama,
     * kita cukup mengambil record pertama (`Profile::first()`).
     */

    public function index(): JsonResponse
    {
        $profile = Profile::first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile data not found',
                'data' => null,
            ], 404);
        }

        $skills = Skill::pluck('name')->toArray(); // Mengambil array nama skill saja
        $projects = Project::all();
        $experiences = Experience::all();

        return response()->json([
            'success' => true,
            'message' => 'Profile data retrieved successfully',
            'data' => [
                'name' => $profile->name,
                'role' => $profile->headline,
                'bio' => $profile->bio,
                'avatar' => $profile->avatar ? asset('storage/' . $profile->avatar) : null,

                'skills' => $skills,
                'projects' => $projects,
                'experiences' => $experiences,
            ]
        ], 200);
    }
}
