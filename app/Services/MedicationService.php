<?php

namespace App\Services;

use App\Models\Medication;

class MedicationService
{
    public function searchMedications(string $searchQuery)
    {
        $search = strtolower($searchQuery);
        
        return Medication::where('status', 'active')
            ->where(function($query) use ($search) {
                $query->whereRaw('LOWER(generic_name) LIKE ?', ["%{$search}%"])
                      ->orWhereRaw('LOWER(commercial_name) LIKE ?', ["%{$search}%"]);
            })
            ->limit(20)
            ->get();
    }

    public function findOrCreateMedication(string $name): Medication
    {
        $cleanName = trim($name);

        $existing = Medication::where('status', 'active')
            ->where(function($query) use ($cleanName) {
                $query->whereRaw('LOWER(generic_name) = ?', [strtolower($cleanName)])
                      ->orWhereRaw('LOWER(commercial_name) = ?', [strtolower($cleanName)]);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        return Medication::create([
            'generic_name' => $cleanName,
            'status' => 'active'
        ]);
    }
}
