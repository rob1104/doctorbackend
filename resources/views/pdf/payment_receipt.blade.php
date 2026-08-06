<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta - {{ $payment->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .details {
            width: 100%;
            margin-bottom: 20px;
        }
        .details th {
            text-align: left;
            padding: 8px;
            background-color: #f8f9fa;
        }
        .details td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <!-- Puedes colocar el logo aquí si está disponible -->
        <h1>Consultorio Médico</h1>
        <p>Nota de Venta / Recibo de Pago</p>
    </div>

    <table class="details" cellspacing="0">
        <tr>
            <th>Folio:</th>
            <td>{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <th>Fecha de Emisión:</th>
            <td>{{ now()->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <th>Paciente:</th>
            <td>{{ optional($payment->consultation->patient)->first_name }} {{ optional($payment->consultation->patient)->last_name }}</td>
        </tr>
        <tr>
            <th>Fecha de Consulta:</th>
            <td>{{ optional($payment->consultation)->created_at ? $payment->consultation->created_at->format('d/m/Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <th>Concepto:</th>
            <td>Consulta Médica</td>
        </tr>
        <tr>
            <th>Forma de Pago:</th>
            <td>
                @php
                    $methods = [
                        '01' => 'Efectivo',
                        '03' => 'Transferencia electrónica',
                        '04' => 'Tarjeta de crédito',
                        '28' => 'Tarjeta de débito',
                        '99' => 'Por definir',
                    ];
                    $methodName = $methods[$payment->payment_method] ?? $payment->payment_method;
                @endphp
                {{ $methodName }}
            </td>
        </tr>
        <tr>
            <th>Estado:</th>
            <td>{{ $payment->paid ? 'Pagado (' . $payment->paid_at->format('d/m/Y H:i') . ')' : 'Pendiente' }}</td>
        </tr>
        <tr>
            <th>Monto Total:</th>
            <td class="total">${{ number_format($payment->amount, 2) }}</td>
        </tr>
        @if($payment->comments)
        <tr>
            <th>Observaciones:</th>
            <td>{{ $payment->comments }}</td>
        </tr>
        @endif
        <tr>
            <th>Registrado por:</th>
            <td>{{ optional($payment->creator)->name ?? 'Sistema' }}</td>
        </tr>
    </table>

    <div class="footer">
        Este documento es una nota de venta y no constituye un comprobante fiscal.
    </div>

</body>
</html>
