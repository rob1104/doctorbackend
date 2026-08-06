<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Medication;

class MedicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/data/medications.csv');

        if (!file_exists($csvFile)) {
            $this->command->warn("El archivo medications.csv no existe en {$csvFile}");
            return;
        }

        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // saltar encabezados

        while (($row = fgetcsv($file)) !== false) {
            $genericName = trim($row[0] ?? '');
            $commercialName = trim($row[1] ?? '');
            $presentation = trim($row[2] ?? '');
            $activeSubstance = trim($row[3] ?? '');
            $route = trim($row[4] ?? '');
            $concentration = trim($row[5] ?? '');

            if (empty($genericName)) {
                continue;
            }

            Medication::updateOrCreate(
                [
                    'generic_name' => $genericName,
                    'commercial_name' => $commercialName,
                    'presentation' => $presentation,
                ],
                [
                    'active_substance' => $activeSubstance,
                    'route' => $route,
                    'concentration' => $concentration,
                    'status' => 'active',
                ]
            );
        }

        fclose($file);
        $this->command->info('Medicamentos importados correctamente desde CSV.');
    }
}
