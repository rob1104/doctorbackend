<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Médica - {{ $prescription->folio }}</title>
    <style>
        @page {
            margin: 0cm; /* Remove page margins to control it via CSS */
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #1e293b;
        }
        .half-page {
            /* Letter height is 27.94cm. Half is 13.97cm */
            height: 12.3cm; /* 13.97 - 1.6 (padding) = 12.37 */
            max-height: 12.3cm;
            padding: 0.8cm 1.5cm;
            overflow: hidden;
            position: relative;
            page-break-inside: avoid;
        }
        .cut-line {
            border-top: 1px dashed #94a3b8;
            width: 100%;
            margin: 0;
            padding: 0;
            height: 0;
        }
        
        .header {
            border-bottom: 1px solid #047857;
            padding-bottom: 5px;
            margin-bottom: 8px;
            width: 100%;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        
        .doctor-name { font-size: 14pt; font-weight: bold; color: #047857; margin: 0; }
        .specialty { font-size: 8.5pt; color: #475569; margin: 0; font-weight: bold; text-transform: uppercase; }
        .credentials { font-size: 7.5pt; color: #64748b; margin: 0; }
        
        .patient-box {
            border: 1px solid #e2e8f0;
            border-left: 3px solid #047857;
            background-color: #f8fafc;
            padding: 4px 6px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        .patient-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .patient-table td { padding: 1px 4px; }
        .label { font-weight: bold; color: #475569; }

        .rx-title {
            font-size: 14pt;
            font-weight: bold;
            color: #047857;
            font-style: italic;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 1px;
            margin-bottom: 8px;
        }

        .medications-container {
            font-size: 8pt;
            height: 6cm; /* Force fixed height */
            max-height: 6cm;
            overflow: hidden; /* Prevent overflow onto next page */
        }
        .medication-item {
            margin-bottom: 4px;
        }
        .medication-name {
            font-weight: bold;
            color: #0f172a;
        }
        .medication-instructions {
            color: #334155;
            padding-left: 8px;
        }

        .general-instructions {
            margin-top: 8px;
            padding: 6px;
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            font-size: 8pt;
        }
        .general-instructions h4 { margin: 0 0 2px 0; font-size: 8pt; color: #166534; }
        .general-instructions p { margin: 0; color: #166534; }

        .footer-area {
            position: absolute;
            bottom: 0.5cm;
            left: 1.5cm;
            right: 1.5cm;
            text-align: center;
        }
        .signature-line {
            width: 150px;
            border-bottom: 1px solid #000;
            margin: 0 auto 3px auto;
        }
        .signature-name { font-weight: bold; font-size: 8.5pt; color: #1e293b; }
        .clinic-info { font-size: 7.5pt; color: #64748b; margin-top: 5px; }
        .legends { font-size: 6.5pt; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 3px; margin-top: 3px; line-height: 1.1; }
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
                        @if(!empty($setting->logo_path))
                            <img src="{{ public_path('storage/' . $setting->logo_path) }}" style="max-width: 60px; max-height: 60px; object-fit: contain;" />
                        @else
                            <img src="{{ public_path('derma_logo.jpg') }}" style="max-width: 60px; max-height: 60px;" />
                        @endif
                    </td>
                    <td style="width: 55%;">
                        <div class="doctor-name">{{ $setting->doctor_name ?? 'Dr. [Nombre del Médico]' }}</div>
                        <div class="specialty">{{ $setting->specialty ?? 'Dermatología Clínica y Estética' }}</div>
                        <div class="credentials">
                            {{ $setting->university ? $setting->university . ' | ' : '' }}
                            Cédula Prof: {{ $setting->professional_id ?? 'XXXXXXX' }} 
                            @if(!empty($setting->specialty_id))
                            | Cédula Esp: {{ $setting->specialty_id }}
                            @endif
                        </div>
                    </td>
                    <td style="width: 30%; text-align: right; vertical-align: bottom;">
                        <div style="font-size: 10pt; font-weight: bold; color: #0f172a;">Folio: <span style="color: #dc2626;">{{ $prescription->folio }}</span></div>
                        <div style="font-size: 7.5pt; color: #64748b;">Fecha: {{ $prescription->created_at->format('d/m/Y H:i') }}</div>
                        <div style="font-size: 7.5pt; color: #64748b; margin-top: 2px; font-weight: bold;">
                            COPIA: {{ $i === 0 ? 'PACIENTE' : 'MÉDICO' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Patient Info -->
        <div class="patient-box">
            <table class="patient-table">
                <tr>
                    <td width="65%"><span class="label">Paciente:</span> {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}</td>
                    <td width="35%"><span class="label">Edad:</span> {{ $prescription->patient->date_of_birth ? \Carbon\Carbon::parse($prescription->patient->date_of_birth)->age . ' años' : 'No especificada' }}</td>
                </tr>
                <tr>
                    <td><span class="label">Diagnóstico:</span> {{ $prescription->consultation->diagnosis ?? 'Reservado' }}</td>
                    <td><span class="label">Alergias:</span> {{ $prescription->patient->allergies ?: 'Ninguna conocida' }}</td>
                </tr>
            </table>
        </div>

        <div class="rx-title">Rx.</div>

        <!-- Medications List -->
        <div class="medications-container">
            @if($prescription->medications && is_array($prescription->medications))
                <table style="width: 100%; border-collapse: collapse;">
                @foreach($prescription->medications as $med)
                    <tr>
                        <td style="padding-bottom: 4px;">
                            @php
                                $displayName = '';
                                if (!empty($med['commercial_name'])) {
                                    $displayName .= strtoupper($med['commercial_name']);
                                    if (!empty($med['active_substance'])) {
                                        $displayName .= ' - ' . strtoupper($med['active_substance']);
                                    }
                                } else {
                                    $displayName .= strtoupper($med['name'] ?? '');
                                }
                            
                                if (!empty($med['concentration'])) {
                                    $displayName .= ' ' . $med['concentration'];
                                }
                                if (!empty($med['route'])) {
                                    $displayName .= ', Vía ' . strtolower($med['route']);
                                }
                            @endphp
                            <div class="medication-name">• {{ $displayName }}</div>
                            <div class="medication-instructions">{{ $med['instructions'] ?? '' }}</div>
                        </td>
                    </tr>
                @endforeach
                </table>
            @endif

            @if($prescription->instructions)
                <div class="general-instructions">
                    <h4>Indicaciones Generales</h4>
                    <p>{!! nl2br(e($prescription->instructions)) !!}</p>
                </div>
            @endif
        </div>

        <!-- Flowing Footer Area -->
        <div class="footer-area">
            <div class="signature-line"></div>
            <div class="signature-name">{{ $setting->doctor_name ?? 'Dr. Médico Tratante' }}</div>
            <div style="font-size: 7.5pt; color: #64748b;">Firma del Médico</div>

            <div class="clinic-info">
                <strong>Consultorio:</strong> {{ $setting->clinic_address ?? 'Dirección de la clínica' }} <br>
                @if(!empty($setting->clinic_phone))
                    <strong>Tel:</strong> {{ $setting->clinic_phone }} &nbsp;&nbsp;&nbsp;
                @endif
                @if(!empty($setting->email))
                    <strong>Email:</strong> {{ $setting->email }}
                @endif
            </div>
            
            <div class="legends">
                Receta médica expedida bajo la normatividad vigente. Sujeto a surtido según indicaciones.<br>
                En caso de presentar reacciones adversas, suspender el medicamento y comunicarse de inmediato.
            </div>
        </div>

    </div>
    
    @if ($i === 0)
    <div class="cut-line"></div>
    @endif

@endfor

</body>
</html>
