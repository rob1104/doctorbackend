<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgendaSetting;

class AgendaSettingController extends Controller
{
    public function show()
    {
        return response()->json(AgendaSetting::getSettings());
    }

    public function update(Request $request)
    {
        $settings = AgendaSetting::getSettings();

        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|in:15,30,45,60',
            'consultation_price' => 'required|integer|min:0',
            'working_days' => 'required|array',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i|after:break_start_time',
            'require_otp' => 'required|boolean'
        ]);

        $settings->update($validated);

        return response()->json($settings);
    }
}
