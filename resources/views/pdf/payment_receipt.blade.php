<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta - {{ $payment->id }}</title>
    <style>
        @page { margin: 0cm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0; padding: 0;
            color: #1e293b;
        }
        .half-page {
            /* Letter height is 27.94cm. Half is 13.97cm */
            height: 12.3cm; 
            padding: 0.8cm 1.5cm;
            overflow: hidden;
            position: relative;
        }
        .cut-line {
            border-top: 1px dashed #94a3b8;
            width: 100%; margin: 0; padding: 0; height: 0;
        }
        .header {
            border-bottom: 1px solid #10b981;
            padding-bottom: 5px;
            margin-bottom: 8px;
            width: 100%;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        
        .doctor-name { font-size: 14pt; font-weight: bold; color: #10b981; margin: 0; }
        .specialty { font-size: 8.5pt; color: #475569; margin: 0; font-weight: bold; text-transform: uppercase; }
        .credentials { font-size: 7.5pt; color: #64748b; margin: 0; }
        
        .title-bar {
            background-color: #f8fafc;
            text-align: center;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
        }
        .title-bar h2 { margin: 0; font-size: 11pt; color: #334155; text-transform: uppercase; letter-spacing: 1px;}
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9pt; }
        .info-table th { text-align: left; padding: 4px 8px; color: #475569; width: 22%; }
        .info-table td { padding: 4px 8px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
        
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 9.5pt; border: 1px solid #e2e8f0; }
        .receipt-table th { background-color: #f1f5f9; color: #475569; padding: 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .receipt-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        .receipt-table .amount { text-align: right; font-weight: bold; }
        
        .total-row td { background-color: #f8fafc; font-size: 11pt; font-weight: bold; color: #0f172a; border-top: 2px solid #cbd5e1; }
        
        .footer {
            position: absolute;
            bottom: 0.8cm;
            left: 1.5cm;
            right: 1.5cm;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
        }
        .status-paid { background-color: #10b981; }
        .status-pending { background-color: #f59e0b; }
    </style>
</head>
<body>

@for ($i = 0; $i < 2; $i++)
    <div class="half-page">
        <!-- Header -->
        <div class="header">
            <table>
                <tr>
                    <td style="width: 15%; text-align: left;">
                        @if($setting && $setting->logo_path)
                            <img src="{{ public_path('storage/' . $setting->logo_path) }}" alt="Logo" style="max-width: 70px; max-height: 70px;">
                        @endif
                    </td>
                    <td style="width: 60%; text-align: center;">
                        <h1 class="doctor-name">{{ $setting->doctor_name ?? 'Dr. Sobrevilla' }}</h1>
                        <p class="specialty">{{ $setting->specialty ?? 'Especialista' }}</p>
                        <p class="credentials">
                            @if($setting && $setting->university) Egresado de: {{ $setting->university }} <br> @endif
                            @if($setting && $setting->professional_license) Cédula Prof: {{ $setting->professional_license }} @endif
                        </p>
                    </td>
                    <td style="width: 25%; text-align: right; font-size: 8pt; color: #64748b; line-height: 1.3;">
                        <strong>Folio:</strong> {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}<br>
                        <strong>Fecha:</strong> {{ now()->format('d/m/Y') }}<br>
                        <strong>Hora:</strong> {{ now()->format('H:i') }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="title-bar">
            <h2>Nota de Venta {{ $i == 0 ? '(Copia Original)' : '(Copia Paciente)' }}</h2>
        </div>

        <table class="info-table">
            <tr>
                <th>Paciente:</th>
                <td colspan="3">{{ optional($payment->consultation->patient)->first_name }} {{ optional($payment->consultation->patient)->last_name }}</td>
            </tr>
            <tr>
                <th>Fecha Cita:</th>
                <td>{{ optional($payment->consultation)->created_at ? $payment->consultation->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                <th>Atendió:</th>
                <td>{{ optional($payment->creator)->name ?? 'Personal de Clínica' }}</td>
            </tr>
        </table>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th style="width: 70%;">Concepto / Descripción</th>
                    <th style="width: 30%; text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Consulta Médica<br>
                        <span style="font-size: 7.5pt; color: #64748b;">
                            Forma de Pago: 
                            @php
                                $methods = [
                                    '01' => 'Efectivo', '03' => 'Transferencia electrónica',
                                    '04' => 'Tarjeta de crédito', '28' => 'Tarjeta de débito', '99' => 'Por definir',
                                ];
                            @endphp
                            {{ $methods[$payment->payment_method] ?? ($payment->payment_method ?: 'No especificada') }}
                        </span>
                        @if($payment->comments)
                            <br><span style="font-size: 7.5pt; color: #64748b; font-style: italic;">Obs: {{ $payment->comments }}</span>
                        @endif
                    </td>
                    <td class="amount">${{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td style="text-align: right;">TOTAL:</td>
                    <td class="amount">${{ number_format($payment->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 15px;">
            <span class="status-badge {{ $payment->paid ? 'status-paid' : 'status-pending' }}">
                {{ $payment->paid ? 'PAGADO (' . ($payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '') . ')' : 'PENDIENTE DE PAGO' }}
            </span>
        </div>

        <div class="footer">
            {{ $setting->clinic_name ?? 'Clínica Médica' }} | {{ $setting->clinic_phone ?? '' }} | {{ $setting->clinic_address ?? '' }}<br>
            Este documento es un comprobante de control interno y no constituye un comprobante fiscal.
        </div>
    </div>

    @if($i == 0)
        <div class="cut-line"></div>
    @endif
@endfor

</body>
</html>
