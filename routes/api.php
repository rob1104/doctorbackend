<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AppointmentController;

Route::post('/login', [AuthController::class, 'login']);

// Rutas Públicas de Citas
Route::get('/availability', [AppointmentController::class, 'availability']);
Route::post('/otp/send', [AppointmentController::class, 'sendOtp']);
Route::post('/appointments', [AppointmentController::class, 'store']);

// Agente IA
Route::post('/ai/medical-agent/chat', [\App\Http\Controllers\AI\MedicalAgentController::class, 'chat'])
    ->middleware('throttle:30,1');

// Rutas Privadas / Administrativas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    
    // Citas
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);

    // Expediente (Pacientes)
    Route::get('/patients/all', [\App\Http\Controllers\PatientController::class, 'all']);
    Route::get('/patients', [\App\Http\Controllers\PatientController::class, 'index']);
    Route::post('/patients', [\App\Http\Controllers\PatientController::class, 'store']);
    Route::get('/patients/{id}', [\App\Http\Controllers\PatientController::class, 'show']);
    Route::put('/patients/{id}', [\App\Http\Controllers\PatientController::class, 'update']);
    Route::delete('/patients/{id}', [\App\Http\Controllers\PatientController::class, 'destroy']);
    Route::patch('/patients/{id}/convert', [\App\Http\Controllers\PatientController::class, 'convert']);

    // Documentos del Paciente
    Route::get('/patients/{patient}/documents', [\App\Http\Controllers\PatientDocumentController::class, 'index']);
    Route::post('/patients/{patient}/documents', [\App\Http\Controllers\PatientDocumentController::class, 'store']);
    Route::delete('/patients/{patient}/documents/{document}', [\App\Http\Controllers\PatientDocumentController::class, 'destroy']);

    // Consultas
    Route::post('/consultations', [\App\Http\Controllers\ConsultationController::class, 'store']);
    Route::put('/consultations/{id}', [\App\Http\Controllers\ConsultationController::class, 'update']);
    Route::patch('/consultations/{id}/finish', [\App\Http\Controllers\ConsultationController::class, 'finish']);
    Route::get('/consultations/{id}/pdf', [\App\Http\Controllers\ConsultationController::class, 'generatePdf']);

    // Recetas
    Route::post('/prescriptions', [\App\Http\Controllers\PrescriptionController::class, 'store']);
    Route::get('/prescriptions/{id}/pdf', [\App\Http\Controllers\PrescriptionController::class, 'generatePdf']);

    // Medicamentos
    Route::get('/medications/all', [\App\Http\Controllers\MedicationController::class, 'index']);
    Route::get('/medications', [\App\Http\Controllers\MedicationController::class, 'search']);
    Route::post('/medications', [\App\Http\Controllers\MedicationController::class, 'store']);
    Route::put('/medications/{id}', [\App\Http\Controllers\MedicationController::class, 'update']);
    Route::delete('/medications/{id}', [\App\Http\Controllers\MedicationController::class, 'destroy']);

    // Configuración Membrete y Agenda
    Route::get('/prescription-settings', [\App\Http\Controllers\PrescriptionSettingController::class, 'show']);
    Route::post('/prescription-settings', [\App\Http\Controllers\PrescriptionSettingController::class, 'update']);
    Route::get('/agenda-settings', [\App\Http\Controllers\AgendaSettingController::class, 'show']);
    Route::post('/agenda-settings', [\App\Http\Controllers\AgendaSettingController::class, 'update']);

    // Dashboard Citas (Admin)
    Route::get('/appointments', [\App\Http\Controllers\AppointmentController::class, 'index']);
    Route::post('/appointments/admin', [\App\Http\Controllers\AppointmentController::class, 'adminStore']);
    Route::patch('/appointments/{id}/status', [\App\Http\Controllers\AppointmentController::class, 'updateStatus']);
    
    // Control de Disponibilidad (Admin)
    Route::get('/availability/blocked', [\App\Http\Controllers\AppointmentController::class, 'getBlockedSlots']);
    Route::post('/availability/toggle', [\App\Http\Controllers\AppointmentController::class, 'toggleBlockedSlot']);
    Route::post('/availability/toggle-all', [\App\Http\Controllers\AppointmentController::class, 'toggleAllBlockedSlots']);

    // WhatsApp Chat (Admin)
    Route::get('/whatsapp/{phone}', [\App\Http\Controllers\WhatsAppController::class, 'history']);
    Route::post('/whatsapp/send', [\App\Http\Controllers\WhatsAppController::class, 'send']);

    // Cobranza
    Route::get('/cobranza', [\App\Http\Controllers\ConsultationPaymentController::class, 'index']);
    Route::get('/cobranza/report/pdf', [\App\Http\Controllers\ConsultationPaymentController::class, 'generateReportPdf']);
    Route::post('/cobranza', [\App\Http\Controllers\ConsultationPaymentController::class, 'store']);
    Route::get('/cobranza/{id}', [\App\Http\Controllers\ConsultationPaymentController::class, 'show']);
    Route::put('/cobranza/{id}', [\App\Http\Controllers\ConsultationPaymentController::class, 'update']);
    Route::get('/cobranza/{id}/pdf', [\App\Http\Controllers\ConsultationPaymentController::class, 'generatePdf']);
    // Usuarios
    Route::apiResource('users', \App\Http\Controllers\UserController::class);
    // Notificaciones
    Route::get('/notifications', [\App\Http\Controllers\AdminNotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\AdminNotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\AdminNotificationController::class, 'markAsRead']);
});

// Autenticación de Broadcasting (Reverb/Pusher) para SPA con Sanctum
Route::post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum');

// Webhook de WhatsApp (recibe mensajes de Node.js, puede no usar sanctum porque es interno)
Route::post('/whatsapp/webhook', [\App\Http\Controllers\WhatsAppController::class, 'webhook']);
