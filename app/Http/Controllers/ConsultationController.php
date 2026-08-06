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
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric',
            'heart_rate' => 'nullable|integer',
            'respiratory_rate' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
        ]);

        $consultation = Consultation::create($validated);
        
        return response()->json($consultation, 201);
    }

    public function update(Request $request, $id, \App\Services\MedicationService $medicationService)
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
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric',
            'heart_rate' => 'nullable|integer',
            'respiratory_rate' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            
            // For Prescription logic
            'issue_prescription' => 'boolean',
            'medications' => 'nullable|array',
            'prescription_instructions' => 'nullable|string',
        ]);

        $consultation->update($request->only([
            'reason', 'physical_exam', 'diagnosis', 'treatment_plan', 'notes',
            'blood_pressure', 'temperature', 'heart_rate', 'respiratory_rate', 'weight', 'height'
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

            $inputMedications = $request->input('medications', []);
            $processedMedications = [];
            
            foreach ($inputMedications as $med) {
                $name = $med['medication']['generic_name'] ?? $med['medication'] ?? $med['name'] ?? null;
                
                if ($name) {
                    $medicationRecord = $medicationService->findOrCreateMedication(is_array($med['medication'] ?? null) ? $name : $name);
                    
                    $processedMedications[] = [
                        'medication_id' => $medicationRecord->id,
                        'name' => $medicationRecord->generic_name,
                        'commercial_name' => $medicationRecord->commercial_name,
                        'active_substance' => $medicationRecord->active_substance,
                        'concentration' => $medicationRecord->concentration,
                        'route' => $medicationRecord->route,
                        'instructions' => $med['instructions'] ?? ''
                    ];
                }
            }

            $prescription->medications = $processedMedications;
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

        // Crear registro de pago pendiente
        \App\Models\ConsultationPayment::firstOrCreate(
            ['consultation_id' => $consultation->id],
            [
                'amount' => 500.00, // Default amount, ideally from a settings table
                'paid' => false
            ]
        );

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
