<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::with(['consultations' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->where('is_patient', true)->orderBy('last_name')->get();
        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:patients,phone',
            'email' => 'nullable|email',
        ]);
        
        $validated['is_patient'] = true;
        
        $patient = Patient::create($validated);
        return response()->json($patient, 201);
    }

    public function show($id)
    {
        $patient = Patient::with(['consultations' => function ($query) {
            $query->orderBy('created_at', 'desc')->with(['prescription', 'payments']);
        }, 'appointments' => function ($query) {
            $query->orderBy('appointment_date', 'desc')->orderBy('start_time', 'desc');
        }])->findOrFail($id);
        
        return response()->json($patient);
    }

    public function update(Request $request, $id)
    {
        $patient = Patient::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:20|unique:patients,phone,'.$id,
            'email' => 'nullable|email',
            'blood_type' => 'nullable|string',
            'allergies' => 'nullable|string',
            'skin_type' => 'nullable|string',
            'family_history' => 'nullable|string',
            'chronic_conditions' => 'nullable|string',
            'gender' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'neighborhood' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'marital_status' => 'nullable|string',
            'occupation' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'surgical_history' => 'nullable|string',
            'skin_tendency' => 'nullable|string',
            'sun_exposure_level' => 'nullable|string',
            'previous_skin_conditions' => 'nullable|string',
            'skincare_routine' => 'nullable|string',
            'non_pathological_history' => 'nullable|string',
            'gyneco_obstetric_history' => 'nullable|string',
        ]);

        $patient->update($validated);
        return response()->json($patient);
    }

    public function destroy($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();
        return response()->json(['message' => 'Paciente eliminado']);
    }

    public function convert($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->is_patient = true;
        $patient->save();
        return response()->json($patient);
    }
}
