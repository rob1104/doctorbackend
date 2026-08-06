<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Medication;

class ImportMedications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:medications {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa medicamentos desde un archivo CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file) || !is_readable($file)) {
            $this->error("El archivo no existe o no se puede leer: {$file}");
            return 1;
        }

        $header = null;
        $data = [];
        
        if (($handle = fopen($file, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        $this->info("Se encontraron " . count($data) . " medicamentos en el archivo CSV.");

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        $count = 0;
        foreach ($data as $row) {
            // Usa updateOrCreate con los campos de la llave única ('medication_unique')
            Medication::updateOrCreate(
                [
                    'generic_name' => $row['generic_name'],
                    'commercial_name' => $row['commercial_name'],
                    'presentation' => $row['presentation'],
                ],
                [
                    'concentration' => $row['concentration'],
                    'active_substance' => $row['active_substance'],
                    'route' => $row['route'],
                    'status' => $row['status'] ?? 'active',
                ]
            );
            $count++;
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\n¡Importación completada! Se procesaron {$count} medicamentos.");
        
        return 0;
    }
}
