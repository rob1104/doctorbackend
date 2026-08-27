<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:20|max:50',
            'heart_rate' => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:3',
        ], [
            'blood_pressure.max' => 'La presión arterial no debe exceder 20 caracteres.',
            'temperature.max' => 'La temperatura parece incorrecta (máximo 50 °C).',
            'temperature.min' => 'La temperatura parece incorrecta (mínimo 20 °C).',
            'heart_rate.max' => 'Frecuencia cardíaca inválida (máx 300).',
            'respiratory_rate.max' => 'Frecuencia respiratoria inválida (máx 100).',
            'weight.max' => 'El peso parece incorrecto (máximo 500 kg).',
            'height.max' => 'La talla parece incorrecta (máximo 3 m).',
            'numeric' => 'Este campo debe ser un número.',
            'integer' => 'Este campo debe ser un número entero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación en los datos capturados.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $validated['user_id'] = auth()->id() ?? 1;
        $consultation = Consultation::create($validated);
        
        return response()->json($consultation, 201);
    }

    public function update(Request $request, $id, \App\Services\MedicationService $medicationService)
    {
        $consultation = Consultation::findOrFail($id);

        if ($consultation->is_finished) {
            return response()->json(['message' => 'Cannot edit a finished consultation'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'reason' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:20|max:50',
            'heart_rate' => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:3',
            
            // For Prescription logic
            'issue_prescription' => 'boolean',
            'medications' => 'nullable|array',
            'prescription_instructions' => 'nullable|string',
        ], [
            'blood_pressure.max' => 'La presión arterial no debe exceder 20 caracteres.',
            'temperature.max' => 'La temperatura parece incorrecta (máximo 50 °C).',
            'temperature.min' => 'La temperatura parece incorrecta (mínimo 20 °C).',
            'heart_rate.max' => 'Frecuencia cardíaca inválida (máx 300).',
            'respiratory_rate.max' => 'Frecuencia respiratoria inválida (máx 100).',
            'weight.max' => 'El peso parece incorrecto (máximo 500 kg).',
            'height.max' => 'La talla parece incorrecta (máximo 3 m).',
            'numeric' => 'Este campo debe ser un número.',
            'integer' => 'Este campo debe ser un número entero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación en los datos capturados.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

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

        $settings = \App\Models\AgendaSetting::getSettings();
        $defaultPrice = $settings->consultation_price ?? 1500;

        // Crear registro de pago pendiente
        \App\Models\ConsultationPayment::firstOrCreate(
            ['consultation_id' => $consultation->id],
            [
                'amount' => $defaultPrice,
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
