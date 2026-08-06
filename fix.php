<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Consultation::find(6);
if ($c && !$c->appointment_id) {
    $a = \App\Models\Appointment::create([
        'patient_id' => $c->patient_id,
        'type' => 'clinico',
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->format('H:i:s'),
        'end_time' => now()->addMinutes(30)->format('H:i:s'),
        'status' => 'approved',
        'notes' => 'Generada auto'
    ]);
    $c->appointment_id = $a->id;
    $c->save();
    \App\Models\ConsultationPayment::create([
        'appointment_id' => $a->id,
        'amount' => 500,
        'paid' => false
    ]);
    echo 'Fixed';
}
