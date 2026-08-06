<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StoreConsultationPaymentRequest;
use App\Http\Requests\UpdateConsultationPaymentRequest;
use App\Models\ConsultationPayment;
use App\Services\ConsultationPaymentService;
use Illuminate\Http\JsonResponse;

class ConsultationPaymentController extends Controller
{
    protected ConsultationPaymentService $paymentService;

    public function __construct(ConsultationPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request): JsonResponse
    {
        // $this->authorize('viewAny', ConsultationPayment::class); // if using strict policies
        $filters = $request->all();
        $appointments = $this->paymentService->getList($filters);
        
        return response()->json($appointments);
    }

    public function store(StoreConsultationPaymentRequest $request): JsonResponse
    {
        // $this->authorize('create', ConsultationPayment::class);
        $payment = $this->paymentService->storePayment($request->validated());
        
        return response()->json([
            'message' => 'Pago registrado correctamente',
            'payment' => $payment
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $payment = ConsultationPayment::with(['appointment.patient', 'creator'])->findOrFail($id);
        // $this->authorize('view', $payment);
        
        return response()->json($payment);
    }

    public function update(UpdateConsultationPaymentRequest $request, $id): JsonResponse
    {
        $payment = ConsultationPayment::findOrFail($id);
        // $this->authorize('update', $payment);
        
        $payment = $this->paymentService->updatePayment($payment, $request->validated());
        
        return response()->json([
            'message' => 'Pago actualizado correctamente',
            'payment' => $payment
        ]);
    }

    public function generatePdf($id)
    {
        $payment = ConsultationPayment::findOrFail($id);
        // $this->authorize('view', $payment);
        
        return $this->paymentService->generatePdf($payment);
    }

    public function generateReportPdf(Request $request)
    {
        // $this->authorize('viewAny', ConsultationPayment::class);
        $filters = $request->all();
        return $this->paymentService->generateReportPdf($filters);
    }
}
