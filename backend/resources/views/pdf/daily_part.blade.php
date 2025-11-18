<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parte Diario de Equipos</title>
    <style>
        /* Reset básico optimizado para PDF */
        * {
            margin: 2px, 15px, 15px, 15px;
            padding: 0;
            box-sizing: border-box;
        }

        /* Configuración general del documento - MODIFICADO para footer */
        @page {
            margin: 2mm 15mm 12mm 15mm; /* Aumentado margin-bottom para footer */
            size: A4 portrait;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        /* Contenedor principal - NUEVO */
        .main-content {
            margin-bottom: 85px; /* Espacio reservado para el footer fijo */
        }

        /* Utilidades generales */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }
        .mb-20 { margin-bottom: 20px; }

        /* Header optimizado */
        .header {
            width: 100%;
            margin-bottom: 5px;
            page-break-inside: avoid;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #000;
        }

        .header-table td {
            vertical-align: top;
            padding: 5px;
        }

        .logo-cell {
            width: 80px;
            text-align: center;
        }

        .logo {
            width: 50px;
            height: 60px;
            max-width: 50px;
            max-height: 60px;
            object-fit: contain;
        }

        .logo-trabajo {
            width: 60px;
            height: 60px;
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
        }

        .title-cell {
            width: auto;
            padding: 0 10px;
        }

        .title-cell h1 {
            color: #0066cc;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .title-cell h2 {
            color: #000;
            font-size: 9px;
            font-weight: normal;
            margin: 1px 0;
        }

        .date-cell {
            width: 170px;
            text-align: center;
        }

        .date-box {
            border: 1px solid #0066cc;
            padding: 6px 8px;
            display: inline-block;
            margin: 0 1px;
            font-weight: bold;
            font-size: 9px;
        }

        /* Título del formulario */
        .form-title {
            background-color: #4472C4;
            color: white;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 5px;
            margin: 5px 0;
            page-break-inside: avoid;
        }

        /* Tablas base optimizadas */
        .table-base {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table-base th,
        .table-base td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: middle;
        }

        .table-base th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
        }

        /* Información del equipo */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 5px;
            vertical-align: middle;
            border: none;
        }

        .info-label {
            font-weight: bold;
            white-space: nowrap;
            width: 120px;
        }

        .info-line {
            border-bottom: 1px solid #000;
            min-height: 16px;
            display: inline-block;
            width: 100%;
        }

        /* Tabla de operador - CORREGIDA */
        .operador-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }

        .operador-table th,
        .operador-table td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }

        .operador-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
            padding: 4px;
            height: 4px;
        }

        /* ALTURA ESPECÍFICA PARA CELDAS DE DATOS DE OPERADOR */
        .operador-table tbody td {
            height: 7px !important;
            min-height: 7px !important;
            padding: 7px !important;
        }

        /* Tabla de trabajo */
        .work-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .work-table th,
        .work-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .work-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
        }

        .work-table .hours-col { width: 8%; }
        .work-table .work-col {
            width: 68%;
            text-align: left;
            padding-left: 8px;
        }
        .work-table .total-col { width: 16%; }

        .work-row {
            height: 25px;
        }

        .work-row td {
            height: 15px !important;
        }

        /* Sección de resumen */
        .summary-section {
            width: 100%;
            margin-bottom: 15px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-left {
            width: 55%;
            vertical-align: top;
            padding-right: 10px;
        }

        .summary-right {
            width: 45%;
            vertical-align: top;
        }

        .hours-summary,
        .supplies-summary {
            width: 100%;
            border-collapse: collapse;
        }

        .hours-summary th,
        .hours-summary td,
        .supplies-summary th,
        .supplies-summary td {
            border: 1px solid #000;
            padding: 4px;
        }

        .hours-summary th,
        .supplies-summary th {
            background-color: #E7E6E6;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }

        .hours-summary .label-col {
            text-align: left;
            width: 70%;
        }

        .hours-summary .value-col {
            width: 15%;
            text-align: center;
        }

        .supplies-summary .desc-col {
            width: 60%;
            text-align: left;
        }

        .supplies-summary .cant-col,
        .supplies-summary .unit-col {
            width: 20%;
            text-align: center;
        }

        /* Tabla de horómetro - CORREGIDA */
        .meter-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .meter-table th,
        .meter-table td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }

        .meter-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
            padding: 8px;
            height: 40px;
        }

        .meter-table .meter-col {
            width: 33.33%;
        }

        /* ALTURA ESPECÍFICA PARA CELDAS DE DATOS DE HORÓMETRO */
        .meter-table tbody td {
            height: 50px !important;
            min-height: 50px !important;
            padding: 15px 8px !important;
        }

        /* Observaciones */
        .observations {
            margin-bottom: 2px !important;
        }

        .observations-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /* Firmas optimizadas - MODIFICADO para footer fijo */
        .signatures-section {
            position: fixed;
            bottom: 15mm;
            left: 15mm;
            right: 15mm;
            width: calc(100% - 30mm);
            background-color: white;
            padding-top: 10px;
            page-break-inside: avoid;
            z-index: 1000;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .signatures-table td {
            width: 33.33%; /* 3 columnas iguales */
            padding: 15px 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 30px;
            margin-bottom: 5px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 9px;
        }

        /* Optimizaciones específicas para dompdf */
        img {
            max-width: 100%;
            height: auto;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .total-row {
            height: 15px;
        }

        .total-row td {
            height: 15px !important;
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .hours-main {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            padding: 4px;
        }

        .qr-code-container {
            position: fixed;
            bottom: 3mm;
            left: 15mm;
            right: 15mm;
            width: calc(100% - 30mm);
            height: 20px;
            z-index: 999;
        }

        .qr-verification-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .qr-verification-table td {
            vertical-align: middle;
            padding: 2px;
        }

        .verification-text {
            width: 85%; /* AUMENTADO de calc(100% - 70px) a 85% */
            text-align: left;
            padding-right: 10px;
        }

        .verification-text strong {
            font-size: 8px;
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }

        .url-text {
            font-size: 7px;
            word-wrap: break-word;
            word-break: break-all;
            line-height: 1.3;
            display: block;
        }

        .qr-image {
            width: 15%; /* REDUCIDO para que coincida con el ancho del QR */
            text-align: right;
        }

        .qr-code {
            width: 60px;
            height: 60px;
            max-width: 60px;
            max-height: 60px;
            object-fit: contain;
            display: block;
            margin-left: auto; /* Para alinear a la derecha dentro de su celda */
        }
    </style>
</head>
<body>
    <!-- Contenedor principal - TODO el contenido va aquí excepto firmas -->
    <div class="main-content">
        @php
        $mesesEspanol = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        @endphp

        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if(isset($logoPath) && $logoPath)
                            <img class="logo" src="{{ $logoPath }}" alt="Logo Empresa">
                        @endif
                    </td>

                    <td class="title-cell">
                        <h1>GOBIERNO REGIONAL PUNO</h1>
                        <h2>{{ $service->goal_detail }}</h2>
                    </td>

                    <td class="date-cell">
                        <div style="margin-bottom: 8px;">
                            @if(isset($logoWorkPath) && $logoWorkPath)
                                <img class="logo-trabajo" src="{{ $logoWorkPath }}" alt="Logo Trabajo">
                            @endif
                        </div>
                        <div>
                            <span class="date-box">
                                @foreach ($dailyPart->unique('work_date') as $item)
                                    {{ \Carbon\Carbon::parse($item->work_date)->format('d') }}
                                @endforeach
                            </span>
                            <span class="date-box">
                                @foreach ($dailyPart->unique('work_date') as $item)
                                    {{ $mesesEspanol[\Carbon\Carbon::parse($item->work_date)->format('n')] }}
                                @endforeach
                            </span>
                            <span class="date-box">
                                @foreach ($dailyPart->unique('work_date') as $item)
                                    {{ \Carbon\Carbon::parse($item->work_date)->format('Y') }}
                                @endforeach
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Form Title -->
        <div class="form-title">PARTE DIARIO DE EQUIPOS/MAQUINARIA</div>

        <!-- Equipment Information -->
        <table class="info-table">
            <tr>
                <td class="info-label">OBRA:</td>
                <td colspan="3">
                    <span class="info-line">{{ $service->goal_detail }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-label">PROPIETARIO:</td>
                <td colspan="3">
                    <span class="info-line">{{ $orderSilucia->supplier ?? 'GOBIERNO REGIONAL - EQUIPO MECANICO' }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-label">NOMBRE DEL OPERADOR:</td>
                <td colspan="3">
                    <span class="info-line">
                        {{ $dailyPart->pluck('operator')->unique()->join(', ') }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="info-label">EQUIPO O MAQUINARIA:</td>
                <td style="width: 45%;">
                    <span class="info-line">{{ $orderSilucia->machinery_equipment ?? $mechanicalEquipment->machinery_equipment }}</span>
                </td>
                <td class="info-label" style="width: 10%;">CAPACIDAD:</td>
                <td style="width:65%;">
                    <span class="info-line">{{  $orderSilucia->ability ?? $mechanicalEquipment->ability }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-label">MARCA:</td>
                <td style="width: 45%;">
                    <span class="info-line">{{ $orderSilucia->brand ?? $mechanicalEquipment->brand }}</span>
                </td>
                <td class="info-label" style="width: 10%;">PLACA:</td>
                <td style="width: 65%">
                    <span class="info-line">{{ $orderSilucia->plate ?? $mechanicalEquipment->plate }}</span>
                </td>
            </tr>
            @if(!empty($orderSilucia) && $orderSilucia->serial_number)
            <tr>
                <td class="info-label">SERIE:</td>
                <td style="width: 45%;">
                    <span class="info-line">{{ $orderSilucia->serial_number ?? '-' }}</span>
                </td>
                <td class="info-label" style="width: 10%;">MODELO/AÑO:</td>
                <td style="width: 65%;">
                    <span class="info-line">{{ $orderSilucia->model . ' - ' . $orderSilucia->year ?? '-' }}</span>
                </td>
            </tr>
            @endif
        </table>

        <!-- Operator Table -->
        <table class="operador-table">
            <thead>
                <tr>
                    <th style="width: 50%;">DEL OPERADOR</th>
                    <th style="width: 50%;">TURNO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    @php $part = $dailyPart->first(); @endphp
                    <td colspan="4">
                        @if($part && $part->shift_id == 1)
                            <span>MAÑANA</span>
                        @elseif($part && $part->shift_id == 2)
                            <span>TARDE</span>
                        @elseif($part && $part->shift_id == 3)
                            <span>NOCHE</span>
                        @else
                            <span>Día completo</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Work Table -->
        <table class="work-table">
            <thead>
                <tr>
                    <th colspan="2" class="hours-main" style="border-right: 1px solid #000;">HORAS</th>
                    <th rowspan="2" class="work-col" style="border-left: 1px solid #000;">TRABAJOS REALIZADOS<br>CON EQUIPO Y/O MAQUINARIA</th>
                    <th rowspan="2" class="total-col">TOTAL<br>HORAS</th>
                </tr>
                <tr>
                    <th class="hours-col">DE</th>
                    <th class="hours-col" style="border-right: 1.4px solid #000;">A</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalHours = 0;
                    $totalMinutes = 0;
                @endphp

                @foreach($dailyPart as $index => $part)
                    <tr class="work-row">
                        @if($service->medida_id === 27){
                            <td>-</td>
                            <td>-</td>
                        }@else{
                            <td>{{ $part->start_time ? \Carbon\Carbon::parse($part->start_time)->format('H:i') : '' }}</td>
                            <td>{{ $part->end_time ? \Carbon\Carbon::parse($part->end_time)->format('H:i') : '' }}</td>
                        }@endif
                        
                        <td style="text-align: left; padding-left: 8px;">{{ $part->description ?? '' }}</td>
                        @if($service->medida_id === 27){
                            <td>1 dia</td>
                        }@else{
                            <td>
                                @if($part->start_time && $part->end_time)
                                    @php
                                        $start = \Carbon\Carbon::parse($part->start_time);
                                        $end = \Carbon\Carbon::parse($part->end_time);
                                        if ($end->lt($start)) {
                                            $end->addDay();
                                        }
                                        $diff = $start->diff($end);
                                        $hours = $diff->h + ($diff->days * 24);
                                        $minutes = $diff->i;
                                        $totalHours += $hours;
                                        $totalMinutes += $minutes;
                                    @endphp
                                    {{ sprintf('%02d:%02d', $hours, $minutes) }}
                                @else
                                    {{ $part->time_worked ? \Carbon\Carbon::parse($part->time_worked)->format('H:i') : '' }}
                                @endif
                            </td>
                        }@endif
                        
                    </tr>
                @endforeach

                {{-- Rellenar filas vacías hasta completar 7 filas --}}
                @for($i = count($dailyPart); $i < 7; $i++)
                    <tr class="work-row">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endfor

                <tr class="total-row">
                    <td colspan="3" style="text-align: center; font-weight: bold; background-color: #E7E6E6;">TOTAL</td>
                    @if($service->medida_id === 27){
                        <td>1 dia</td>
                    }@else{
                        <td style="background-color: #E7E6E6;">
                            @php
                                $totalHours += intval($totalMinutes / 60);
                                $totalMinutes = $totalMinutes % 60;
                            @endphp
                            {{ sprintf('%02d:%02d', $totalHours, $totalMinutes) }}
                        </td>
                    }@endif
                    
                </tr>
            </tbody>
        </table>

        <!-- Observations -->
        <div class="observations">
            <div class="observations-label">Ocurrencias:</div>
            <div style="min-height: 18px; padding: 2px 0;">
                @foreach($dailyPart as $part)
                    {{ $part->occurrences }}.
                @endforeach
            </div>
        </div>

        <!-- Horometer Table -->
        <table class="operador-table">
            <tbody>
                <tr>
                    <th style="width: 20%;">KM./HOROMETRO INICIO:</th>
                    <td></td>
                    <th style="width: 20%;">KM./HOMETRO FINAL:</th>
                    <td></td>
                    <th style="width: 20%;">TOTAL</th>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Supplies Summary -->
        <table class="operador-table">
            <tbody>
                <tr>
                    <th style="width: 20%;">GASOHOL</th>
                    <td>{{ $dailyPart->sum('gasolina') }} Gls. </td>
                    <th style="width: 20%;">ACEITE HIDRÁULICO</th>
                    <td>Gls. </td>
                </tr>
                <tr>
                    <th style="width: 20%;">PETRÓLEO</th>
                    <td>{{ $dailyPart->sum('initial_fuel') }} Gls. </td>
                    <th style="width: 20%;">GRASA</th>
                    <td>Gls. </td>
                </tr>
                <tr>
                    <th style="width: 20%;">ACEITE MOTOR</th>
                    <td>Gls. </td>
                    <th style="width: 20%;">FILTRO</th>
                    <td>Gls. </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Signatures - FUERA del main-content para que sea footer fijo -->
    <div class="signatures-section">
        <table class="signatures-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">CONTROLADOR</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">RESIDENTE DE OBRA</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">SUPERVISOR DE OBRA</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="qr-code-container">
        <table class="qr-verification-table">
            <tr>
                <td class="verification-text">
                    <strong>Verificación:</strong>
                    <span class="url-text">{{ $document_url }}</span>
                    <span class="url-text">Documento Generado: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                </td>
                <td class="qr-image">
                    @if(isset($qr_code) && $qr_code)
                        <img class="qr-code" src="data:image/png;base64,{{ $qr_code }}" alt="QR Code">
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
