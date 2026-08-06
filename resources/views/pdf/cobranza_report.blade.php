<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cobranza</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
        }
        .filters-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .filters-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2c3e50;
        }
        .filters-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .filters-section li {
            margin-bottom: 5px;
            font-size: 11px;
        }
        .filters-section li strong {
            color: #34495e;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #2c3e50;
            color: #fff;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            font-size: 11px;
        }
        .amount-col {
            text-align: right;
        }
        .status-badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .summary-section {
            text-align: right;
            margin-top: 20px;
            font-size: 16px;
        }
        .summary-section strong {
            color: #2c3e50;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 10px;
            color: #95a5a6;
            border-top: 1px solid #e9ecef;
            padding-top: 5px;
        }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Reporte de Cobranza y Pagos</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="filters-section">
        <h3>Filtros Aplicados</h3>
        <ul>
            @if(!empty($filters['date_from']) || !empty($filters['date_to']))
                <li><strong>Rango de Fechas:</strong> 
                    {{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Inicio' }} 
                    a 
                    {{ !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Actualidad' }}
                </li>
            @endif
            
            @if(isset($filters['status']) && $filters['status'] !== 'todos')
                <li><strong>Estado:</strong> {{ ucfirst($filters['status']) }}</li>
            @endif

            @if(empty($filters['date_from']) && empty($filters['date_to']) && (!isset($filters['status']) || $filters['status'] == 'todos'))
                <li>Mostrando todos los registros históricos.</li>
            @endif
        </ul>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha Cons.</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th>Estado</th>
                <th>Forma de Pago</th>
                <th>Fecha de Pago</th>
                <th class="amount-col">Monto</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @foreach($consultations as $consultation)
                @php
                    $payment = $consultation->payments->first();
                    $isPaid = $payment && $payment->paid;
                    $amount = $payment ? $payment->amount : ($consultation->cost ?? 0);
                    $totalAmount += $amount;
                    
                    $methodName = 'N/A';
                    if($payment && $payment->payment_method){
                        $methods = ['01' => 'Efectivo', '03' => 'Transferencia', '04' => 'T. Crédito', '28' => 'T. Débito', '99' => 'Por definir'];
                        $methodName = $methods[$payment->payment_method] ?? $payment->payment_method;
                    }
                @endphp
                <tr>
                    <td>{{ $consultation->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $consultation->patient ? $consultation->patient->first_name . ' ' . $consultation->patient->last_name : 'N/A' }}</td>
                    <td>Dr. Sobrevilla</td>
                    <td>
                        @if($isPaid)
                            <span class="status-badge status-paid">PAGADO</span>
                        @else
                            <span class="status-badge status-pending">PENDIENTE</span>
                        @endif
                    </td>
                    <td>{{ $methodName }}</td>
                    <td>{{ ($isPaid && $payment->paid_at) ? \Carbon\Carbon::parse($payment->paid_at)->format('d/m/Y H:i') : '-' }}</td>
                    <td class="amount-col">${{ number_format($amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <strong>Total Cobrado: </strong> ${{ number_format($totalAmount, 2) }}
    </div>

    <div class="footer">
        Reporte generado automáticamente por el Sistema Médico. Página <span class="page-number"></span>
    </div>

</body>
</html>
