<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\PrescriptionSetting;
use App\Services\MedicationService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PrescriptionController extends Controller
{
    protected MedicationService $medicationService;

    public function __construct(MedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'consultation_id' => 'nullable|exists:consultations,id',
            'patient_id' => 'required|exists:patients,id',
            'medications' => 'nullable|array',
            'instructions' => 'nullable|string',
        ]);

        if (isset($validated['medications'])) {
            $processedMedications = [];
            foreach ($validated['medications'] as $med) {
                // Determine medication name
                $name = $med['medication']['generic_name'] ?? $med['medication'] ?? $med['name'] ?? null;
                
                if ($name) {
                    // Si pasaron un string o no tiene ID real (o es nuevo), lo buscamos/creamos
                    $medicationRecord = $this->medicationService->findOrCreateMedication(is_array($med['medication'] ?? null) ? $name : $name);
                    
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
            $validated['medications'] = $processedMedications;
        }

        $setting = PrescriptionSetting::first();
        $folio = 'REC-' . str_pad($setting ? $setting->folio_counter : 1, 5, '0', STR_PAD_LEFT);
        
        if ($setting) {
            $setting->increment('folio_counter');
        }

        $validated['folio'] = $folio;

        $prescription = Prescription::create($validated);
        
        return response()->json($prescription, 201);
    }

    public function generatePdf($id)
    {
        $prescription = Prescription::with(['patient', 'consultation'])->findOrFail($id);
        $setting = PrescriptionSetting::first();

        // Pass to a view and set paper to Letter
        $pdf = Pdf::loadView('pdf.prescription', [
            'prescription' => $prescription,
            'setting' => $setting
        ])->setPaper('letter', 'portrait');
        
        // Return stream
        return $pdf->stream('receta-'.$prescription->folio.'.pdf');
    }
}
