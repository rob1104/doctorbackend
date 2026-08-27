<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\ConsultationPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ConsultationPaymentService
{
    private function buildQuery(array $filters)
    {
        $query = Consultation::with(['patient', 'payments']);

        $query->where('is_finished', true);

        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['status']) && $filters['status'] !== 'todos') {
            if ($filters['status'] === 'pagados') {
                $query->whereHas('payments', function ($q) {
                    $q->where('paid', true);
                });
            } elseif ($filters['status'] === 'pendientes') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('payments', function ($q2) {
                        $q2->where('paid', true);
                    });
                });
            }
        }

        $sortBy = $filters['sortBy'] ?? 'created_at';
        $descending = filter_var($filters['descending'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $direction = $descending ? 'desc' : 'asc';
        
        $query->orderBy($sortBy, $direction);

        return $query;
    }

    public function getList(array $filters)
    {
        return $this->buildQuery($filters)->paginate($filters['rowsPerPage'] ?? 15);
    }

    public function generateReportPdf(array $filters)
    {
        $consultations = $this->buildQuery($filters)->get();
        
        $pdf = Pdf::loadView('pdf.cobranza_report', compact('consultations', 'filters'));
        
        return $pdf->download('reporte_cobranza_'.date('YmdHis').'.pdf');
    }

    public function storePayment(array $data)
    {
        $payment = ConsultationPayment::updateOrCreate(
            ['consultation_id' => $data['consultation_id']],
            [
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'paid' => $data['paid'],
                'paid_at' => $data['paid'] ? now() : null,
                'comments' => $data['comments'] ?? null,
                'created_by' => Auth::id(),
            ]
        );

        Log::info('Pago registrado', [
            'payment_id' => $payment->id,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
        ]);

        return $payment;
    }

    public function updatePayment(ConsultationPayment $payment, array $data)
    {
        $payment->update([
            'amount' => $data['amount'] ?? $payment->amount,
            'payment_method' => $data['payment_method'] ?? $payment->payment_method,
            'paid' => $data['paid'] ?? $payment->paid,
            'paid_at' => (isset($data['paid']) && $data['paid']) ? now() : $payment->paid_at,
            'comments' => $data['comments'] ?? $payment->comments,
        ]);

        Log::info('Pago actualizado', [
            'payment_id' => $payment->id,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
        ]);

        return $payment;
    }

    public function generatePdf(ConsultationPayment $payment)
    {
        $payment->load(['consultation.patient', 'creator']);
        $setting = \App\Models\PrescriptionSetting::first();
        
        $pdf = Pdf::loadView('pdf.payment_receipt', compact('payment', 'setting'))
                  ->setPaper('letter', 'portrait');
        
        return $pdf->download('nota_venta_'.$payment->id.'.pdf');
    }
}
