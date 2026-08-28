<?php

namespace App\Services;

use App\Models\Medication;

class MedicationService
{
    public function searchMedications(string $searchQuery)
    {
        $searchTerms = array_filter(explode(' ', strtolower(trim($searchQuery))));
        
        $query = Medication::where('status', 'active');
        
        foreach ($searchTerms as $term) {
            $query->where(function($q) use ($term) {
                $q->whereRaw('LOWER(generic_name) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(commercial_name) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(presentation) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(concentration) LIKE ?', ["%{$term}%"])
                  ->orWhereRaw('LOWER(route) LIKE ?', ["%{$term}%"]);
            });
        }
        
        return $query->limit(20)->get();
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
