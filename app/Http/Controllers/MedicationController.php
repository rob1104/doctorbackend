<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MedicationService;

class MedicationController extends Controller
{
    protected MedicationService $medicationService;

    public function __construct(MedicationService $medicationService)
    {
        $this->medicationService = $medicationService;
    }

    public function search(Request $request)
    {
        $search = $request->query('search', '');
        
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $results = $this->medicationService->searchMedications($search);
        
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'generic_name' => 'required|string|max:255',
            'active_substance' => 'required|string|max:255',
            'concentration' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'presentation' => 'nullable|string|max:255',
        ]);

        $validated['status'] = 'active';

        $medication = \App\Models\Medication::create($validated);

        return response()->json($medication, 201);
    }

    public function index()
    {
        $medications = \App\Models\Medication::orderBy('generic_name', 'asc')->get();
        return response()->json($medications);
    }

    public function update(Request $request, $id)
    {
        $medication = \App\Models\Medication::findOrFail($id);

        $validated = $request->validate([
            'generic_name' => 'required|string|max:255',
            'active_substance' => 'required|string|max:255',
            'concentration' => 'required|string|max:255',
            'route' => 'required|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'presentation' => 'nullable|string|max:255',
        ]);

        $medication->update($validated);

        return response()->json($medication, 200);
    }

    public function destroy($id)
    {
        $medication = \App\Models\Medication::findOrFail($id);
        $medication->delete();

        return response()->json(['message' => 'Medication deleted'], 200);
    }
}
