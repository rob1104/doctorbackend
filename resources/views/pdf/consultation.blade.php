<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen Clínico - {{ $consultation->id }}</title>
    <style>
        @page {
            margin: 1.5cm 2cm 2.5cm 2cm;
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
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Box styles */
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .box-header {
            background-color: #f1f5f9;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 9pt;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .box-content {
            padding: 8px;
            font-size: 9.5pt;
        }
        
        /* Patient Info Table */
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
        
        /* Signature Area - now part of normal flow */
        .signature-block {
            margin-top: 30px;
            width: 100%;
            text-align: center;
            page-break-inside: avoid;
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
            margin-top: 10px;
            font-size: 8pt;
            color: #64748b;
        }
        .legends {
            margin-top: 10px;
            font-size: 7.5pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
    </style>
</head>
<body>

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
                <p style="margin:0; font-size: 10pt; font-weight: bold; color: #0f172a;">Expediente: {{ str_pad($consultation->patient_id, 5, '0', STR_PAD_LEFT) }}</p>
                <p style="margin: 3px 0 0 0; font-size: 8pt; color: #64748b;">Fecha: {{ $consultation->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="doc-title">
            RESUMEN CLÍNICO / NOTA DE EVOLUCIÓN
        </div>

        <!-- Patient Info -->
        <div class="box">
            <div class="box-header">Datos del Paciente</div>
            <div class="box-content" style="padding: 8px;">
                <table class="patient-table">
                    <tr>
                        <td width="50%"><span class="label">Nombre:</span> {{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</td>
                        <td width="50%"><span class="label">Edad:</span> {{ $consultation->patient->date_of_birth ? \Carbon\Carbon::parse($consultation->patient->date_of_birth)->age . ' años' : 'No especificada' }}</td>
                    </tr>
                    <tr>
                        <td><span class="label">Tipo de Sangre:</span> {{ $consultation->patient->blood_type ?: 'N/E' }}</td>
                        <td><span class="label">Alergias:</span> {{ $consultation->patient->allergies ?: 'Ninguna conocida' }}</td>
                    </tr>
                    @if($consultation->patient->chronic_conditions)
                    <tr>
                        <td colspan="2"><span class="label">Condiciones Crónicas:</span> {{ $consultation->patient->chronic_conditions }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Consultation Details -->
        @if($consultation->reason)
        <div class="box">
            <div class="box-header">Motivo de Consulta</div>
            <div class="box-content">
                {!! nl2br(e($consultation->reason)) !!}
            </div>
        </div>
        @endif

        @if($consultation->physical_exam)
        <div class="box">
            <div class="box-header">Exploración Física</div>
            <div class="box-content">
                {!! nl2br(e($consultation->physical_exam)) !!}
            </div>
        </div>
        @endif

        @if($consultation->diagnosis)
        <div class="box">
            <div class="box-header">Diagnóstico Clínico</div>
            <div class="box-content">
                {!! nl2br(e($consultation->diagnosis)) !!}
            </div>
        </div>
        @endif

        @if($consultation->treatment_plan)
        <div class="box">
            <div class="box-header">Plan de Tratamiento</div>
            <div class="box-content">
                {!! nl2br(e($consultation->treatment_plan)) !!}
            </div>
        </div>
        @endif

        <!-- Signature Area at the end of the content (not fixed) -->
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $setting->doctor_name ?? 'Dr. Médico Tratante' }}</div>
            <div style="font-size: 9pt; color: #64748b; margin-top: 3px;">Firma del Médico</div>

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
                Este documento constituye un resumen clínico extraído del expediente médico electrónico del paciente.<br>
                La información contenida es confidencial y está protegida por el secreto profesional médico.
            </div>
        </div>
    </div>

</body>
</html>
