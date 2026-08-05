<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $consultation = Consultation::create($validated);
        
        return response()->json($consultation, 201);
    }

    public function update(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);

        if ($consultation->is_finished) {
            return response()->json(['message' => 'Cannot edit a finished consultation'], 403);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
            
            // For Prescription logic
            'issue_prescription' => 'boolean',
            'medications' => 'nullable|array',
            'prescription_instructions' => 'nullable|string',
        ]);

        $consultation->update($request->only([
            'reason', 'physical_exam', 'diagnosis', 'treatment_plan', 'notes'
        ]));

        if ($request->input('issue_prescription')) {
            $prescription = \App\Models\Prescription::firstOrNew(['consultation_id' => $consultation->id]);
            
            // If it's new, set patient_id and folio
            if (!$prescription->exists) {
                $prescription->patient_id = $consultation->patient_id;
                $setting = \App\Models\PrescriptionSetting::first();
                $prescription->folio = 'REC-' . str_pad($setting ? $setting->folio_counter : 1, 5, '0', STR_PAD_LEFT);
                if ($setting) {
                    $setting->increment('folio_counter');
                }
            }

            $prescription->medications = $request->input('medications', []);
            $prescription->instructions = $request->input('prescription_instructions', '');
            $prescription->save();
        } else {
            // Delete if they toggled off prescription
            \App\Models\Prescription::where('consultation_id', $consultation->id)->delete();
        }
        
        return response()->json($consultation->load('prescription'), 200);
    }

    public function finish($id)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->is_finished = true;
        $consultation->save();

        return response()->json(['message' => 'Consulta finalizada', 'consultation' => $consultation], 200);
    }

    public function generatePdf($id)
    {
        $consultation = Consultation::with('patient')->findOrFail($id);
        $setting = \App\Models\PrescriptionSetting::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.consultation', [
            'consultation' => $consultation,
            'setting' => $setting
        ])->setPaper('letter', 'portrait');
        
        return $pdf->stream('resumen-'.$consultation->patient_id.'-'.$consultation->id.'.pdf');
    }
}
