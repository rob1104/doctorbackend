<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\PrescriptionSetting;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PrescriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'consultation_id' => 'nullable|exists:consultations,id',
            'patient_id' => 'required|exists:patients,id',
            'medications' => 'nullable|array',
            'instructions' => 'nullable|string',
        ]);

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
