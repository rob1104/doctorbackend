<?php

namespace App\Http\Controllers;

use App\Models\PrescriptionSetting;
use Illuminate\Http\Request;

class PrescriptionSettingController extends Controller
{
    public function show()
    {
        $setting = PrescriptionSetting::first();
        if (!$setting) {
            $setting = PrescriptionSetting::create([
                'doctor_name' => 'Dr. Salvador Sobrevilla',
                'specialty' => 'Dermatología',
                'folio_counter' => 1
            ]);
        }
        return response()->json($setting);
    }

    public function update(Request $request)
    {
        $setting = PrescriptionSetting::first();
        if (!$setting) {
            $setting = new PrescriptionSetting();
        }
        
        $data = $request->except(['logo']);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $setting->fill($data);
        $setting->save();
        return response()->json($setting);
    }
}
