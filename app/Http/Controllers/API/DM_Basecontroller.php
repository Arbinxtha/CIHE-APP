<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\SecurityLog;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Validation\ValidationException;

class DM_Basecontroller extends Controller
{


    public function saveLog(string $action): bool
    {
        try {
            $validator = FacadesValidator::make(
                ['action' => $action],
                [
                    'action' => 'required|string|max:255'
                ]
            );

            if ($validator->fails()) {
                Log::warning('Invalid security log attempt', $validator->errors()->toArray());
                return false;
            }

            // Create the log
            SecurityLog::create([
                'user_id' => Auth::id(),
                'action' => $action
            ]);

            return true;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to save security log: ' . $e->getMessage());
            return false;
        }
    }

    public function getCourseNamebyID($id)
    {
        $course = Course::findorfail($id);
        return $course->name ?? null;
    }
}
