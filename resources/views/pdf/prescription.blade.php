<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Médica - {{ $prescription->folio }}</title>
    <style>
        @page {
            margin: 1.5cm 2cm 3.5cm 2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #334155;
            font-size: 9.5pt;
            line-height: 1.3;
        }
        .container {
            width: 100%;
        }
        .header {
            border-bottom: 1px solid #047857; /* Emerald Green */
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: table;
            width: 100%;
        }
        .header-col {
            display: table-cell;
            vertical-align: middle;
        }
        .doctor-name {
            font-size: 18pt;
            font-weight: bold;
            color: #047857;
            margin: 0 0 2px 0;
        }
        .specialty {
            font-size: 10pt;
            color: #475569;
            margin: 0 0 2px 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .credentials {
            font-size: 8pt;
            color: #64748b;
            margin: 1px 0;
        }
        
        /* Patient Info Box */
        .patient-box {
            border: 1px solid #e2e8f0;
            border-left: 4px solid #047857;
            border-radius: 6px;
            background-color: #f8fafc;
            padding: 6px 10px;
            margin-bottom: 15px;
        }
        .patient-table {
            width: 100%;
            border-collapse: collapse;
        }
        .patient-table td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #475569;
            font-size: 8.5pt;
            text-transform: uppercase;
        }

        /* Rx Title */
        .rx-title {
            font-size: 28pt;
            font-weight: 800;
            color: #047857;
            font-style: italic;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
            margin-bottom: 15px;
        }

        /* Medications */
        .medication-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dotted #cbd5e1;
        }
        .medication-item:last-child {
            border-bottom: none;
        }
        .medication-name {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }
        .medication-instructions {
            font-size: 9.5pt;
            color: #475569;
            padding-left: 10px;
        }

        /* General Instructions */
        .general-instructions {
            margin-top: 20px;
            padding: 10px;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
        }
        .general-instructions h4 {
            color: #166534;
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 10pt;
            text-transform: uppercase;
        }
        .general-instructions p {
            margin: 0;
            color: #166534;
            font-size: 9pt;
        }

        /* Fixed Footer */
        .footer {
            position: fixed;
            bottom: -2cm;
            width: 100%;
            left: 0;
            text-align: center;
        }
        .signature-area {
            text-align: center;
            margin-bottom: 10px;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #000;
            margin: 0 auto 5px auto;
        }
        .signature-name {
            font-weight: bold;
            font-size: 10pt;
            color: #1e293b;
        }
        .clinic-info {
            font-size: 8pt;
            color: #64748b;
        }
        .legends {
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Fixed Footer applied to all pages -->
    <div class="footer">
        <div class="signature-area">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $setting->doctor_name ?? 'Dr. Médico Tratante' }}</div>
            <div style="font-size: 9pt; color: #64748b; margin-top: 3px;">Firma del Médico</div>
        </div>

        <div class="clinic-info">
            <strong>Consultorio:</strong> {{ $setting->clinic_address ?? 'Dirección de la clínica' }} <br>
            @if(!empty($setting->clinic_phone))
                <strong>Tel:</strong> {{ $setting->clinic_phone }} &nbsp;&nbsp;&nbsp;&nbsp;
            @endif
            @if(!empty($setting->email))
                <strong>Email:</strong> {{ $setting->email }}
            @endif
        </div>
        
        <div class="legends">
            Receta médica expedida bajo la normatividad vigente. Sujeto a surtido según indicaciones.<br>
            En caso de presentar reacciones adversas, suspender el medicamento y comunicarse de inmediato al consultorio.
        </div>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-col" style="width: 18%; text-align: left;">
                @if(!empty($setting->logo_path))
                    <img src="{{ public_path('storage/' . $setting->logo_path) }}" style="max-width: 80px; max-height: 80px; object-fit: contain;" />
                @else
                    <img src="{{ public_path('derma_logo.jpg') }}" style="max-width: 80px; max-height: 80px;" />
                @endif
            </div>
            <div class="header-col" style="width: 52%;">
                <h1 class="doctor-name">{{ $setting->doctor_name ?? 'Dr. [Nombre del Médico]' }}</h1>
                <p class="specialty">{{ $setting->specialty ?? 'Dermatología Clínica y Estética' }}</p>
                <p class="credentials">
                    {{ $setting->university ? $setting->university . ' | ' : '' }}
                    Cédula Prof: {{ $setting->professional_id ?? 'XXXXXXX' }} 
                </p>
                @if(!empty($setting->specialty_id))
                <p class="credentials">Cédula Esp: {{ $setting->specialty_id }}</p>
                @endif
            </div>
            <div class="header-col" style="width: 30%; text-align: right; vertical-align: bottom;">
                <p style="margin:0; font-size: 12pt; font-weight: bold; color: #0f172a;">Folio: <span style="color: #dc2626;">{{ $prescription->folio }}</span></p>
                <p style="margin: 3px 0 0 0; font-size: 8pt; color: #64748b;">Fecha: {{ $prescription->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Patient Info -->
        <div class="patient-box">
            <table class="patient-table">
                <tr>
                    <td width="60%"><span class="label">Paciente:</span> {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}</td>
                    <td width="40%"><span class="label">Edad:</span> {{ $prescription->patient->date_of_birth ? \Carbon\Carbon::parse($prescription->patient->date_of_birth)->age . ' años' : 'No especificada' }}</td>
                </tr>
                <tr>
                    <td><span class="label">Diagnóstico:</span> {{ $prescription->consultation->diagnosis ?? 'Reservado' }}</td>
                    <td><span class="label">Alergias:</span> {{ $prescription->patient->allergies ?: 'Ninguna conocida' }}</td>
                </tr>
            </table>
        </div>

        <div class="rx-title">
            Rx.
        </div>

        <!-- Medications List -->
        <div>
            @if($prescription->medications && is_array($prescription->medications))
                @foreach($prescription->medications as $med)
                    <div class="medication-item">
                        <div class="medication-name">• {{ $med['name'] ?? '' }}</div>
                        <div class="medication-instructions">{{ $med['instructions'] ?? '' }}</div>
                    </div>
                @endforeach
            @endif

            @if($prescription->instructions)
                <div class="general-instructions">
                    <h4>Indicaciones Generales y Cuidados</h4>
                    <p>{!! nl2br(e($prescription->instructions)) !!}</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
