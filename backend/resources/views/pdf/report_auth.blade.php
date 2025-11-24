<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Diario de Autorización de Servicio/Maquinaria GRP</title>
    <style>
        /* Reset básico optimizado para PDF */
        * {
            margin: 2px, 15mm, 15mm, 15mm;
            padding: 0;
            box-sizing: border-box;
        }

        /* Configuración general del documento */
        @page {
            margin: 2mm 15mm 15mm 15mm;
            size: A4 portrait;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        /* Utilidades generales */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }

        /* Header optimizado */
        .header {
            width: 100%;
            margin-bottom: 10px;
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

        /* Título del formulario */
        .form-title {
            background-color: #4472C4;
            color: white;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 8px;
            margin: 10px 0;
            page-break-inside: avoid;
        }

        /* Información básica - CORREGIDO */
        .info-section {
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .info-table td {
            padding: 6px 5px;
            vertical-align: middle;
            border: none;
        }

        .info-label {
            font-size: 8pt;
            font-weight: bold;
            white-space: nowrap;
            width: 100px;
        }

        /* Clase para las líneas subrayadas - NUEVO */
        .info-line {
            border-bottom: 1px solid #000;
            min-height: 18px;
            display: inline-block;
            width: 100%;
            padding-left: 5px;
        }

        /* Tabla principal de datos */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px 2px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 8px;
            padding: 6px 2px;
        }

        .data-table .fecha-col { width: 10%; }
        .data-table .hm-col { width: 10%; }
        .data-table .hm-equiv-col { width: 10%; }
        .data-table .combustible-col { width: 10%; }
        .data-table .dias-col { width: 10%; }
        .data-table .costo-col { width: 12%; }
        .data-table .importe-col { width: 15%; }

        .data-table .total-row {
            background-color: #D9D9D9;
            font-weight: bold;
        }

        .data-table .total-row td {
            border-top: 2px solid #000;
        }

        /* Tabla resumen */
        .summary-section {
            margin-top: 20px;
        }

        .summary-title {
            background-color: #4472C4;
            color: white;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            padding: 6px;
            margin-bottom: 5px;
        }

        .summary-table {
            width: 60%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .summary-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
        }

        .summary-table .summary-value {
            font-weight: bold;
            font-size: 10px;
        }

        /* Estilos para fechas no trabajadas */
        .no-work-row {
            background-color: #f9f9f9;
        }

        .work-row {
            background-color: #ffffff;
        }

        /* Optimizaciones específicas para dompdf */
        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .periodo-info {
            text-align: right;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 9px;
        }

        .currency {
            font-family: monospace;
        }
    </style>
</head>
<body>
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
                    <h1>GOBIERNO REGIONAL DE PUNO</h1>
                    <h2>OFICINA REGIONAL DE ADMINISTRACIÓN</h2>
                    <h2>OFICINA DE EQUIPO MECÁNICO</h2>
                </td>
            </tr>
        </table>
    </div>

    <!-- Form Title -->
    <div class="form-title">REPORTE DIARIO DE AUTORIZACIÓN DE SERVICIO/MAQUINARIA GRP</div>

    <!-- Información del proyecto - ESTRUCTURA CORREGIDA -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="info-label">OBRA:</td>
                <td>
                    <span class="info-line">{{ $service->goal_detail }}</span>
                </td>
            </tr>
        </table>
        
        <table class="info-table">
            <tr>
                <td class="info-label">UND ORGANICA SOLICITANTE:</td>
                <td style="width: 400px;">
                    <span class="info-line">SUB GERENCIA DE OBRAS</span>
                </td>
            </tr>
        </table>
        
        <table class="info-table">
            <tr>
                <td class="info-label">OPERADOR:</td>
                <td>
                    <span class="info-line">{{ $service->operator }}</span>
                </td>
            </tr>
        </table>
        
        <table class="info-table">
            <tr>
                <td class="info-label" style="width: 90px;">MAQUINA:</td>
                <td style="width: 145px;">
                    <span class="info-line">{{ $mechanicalEquipment->machinery_equipment ?? $orderSilucia->machinery_equipment }}</span>
                </td>
                <td class="info-label" style="width: 35px;">MARCA:</td>
                <td style="width: 100px;">
                    <span class="info-line">{{ $mechanicalEquipment->brand ?? $orderSilucia->brand }}</span>
                </td>
                <td class="info-label" style="width: 75px;">PLACA/MODELO:</td>
                <td style="width: 100px;">
                    <span class="info-line">{{ 'lkh-458' . ' ' . 'fsf-458' . ' ' . 'lkh-458' }}</span>
                </td>
            </tr>
        </table>
        <table class="info-table">
            <tr>
                <td class="info-label">PERIODO:</td>
                <td>
                    <span class="info-line">DESDE: {{ $minDate }}</span>
                </td>
                <td>
                    <span class="info-line">HASTA: {{ $maxDate }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla principal de datos -->
    <table class="data-table">
        <thead>
            <tr>
                <th class="fecha-col">FECHA</th>
                <th class="hm-col">HM<br>TRABAJADAS</th>
                <th class="hm-equiv-col">HM<br>EQUIVALENTE</th>
                <th class="combustible-col">CONSUMO DE<br>COMBUSTIBLE</th>
                <th class="dias-col">DÍAS<br>TRABAJADOS</th>
                <th class="costo-col">COSTO<br>HORA S/.</th>
                <th class="importe-col">IMPORTE<br>TOTAL S/.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($processedData as $dayData)
                <tr class="{{ $dayData['has_work'] ? 'work-row' : 'no-work-row' }}">
                    <td>{{ $dayData['date'] }}</td>
                    <td>{{ $dayData['time_worked'] }}</td>
                    <td>{{ $dayData['equivalent_hours'] }}</td>
                    <td>{{ $dayData['fuel_consumption'] }}</td>
                    <td>{{ $dayData['days_worked'] }}</td>
                    <td class="currency">S/. {{ $dayData['cost_per_hour'] }}</td>
                    <td class="currency">S/. {{ $dayData['has_work'] ? $dayData['total_amount'] : '0.00' }}</td>
                </tr>
            @endforeach
            
            <!-- Fila de totales -->
            <tr class="total-row">
                <td style="font-weight: bold;">TOTALES</td>
                <td style="font-weight: bold;">{{ $totals['time_worked'] }}</td>
                <td style="font-weight: bold;">{{ $totals['equivalent_hours'] }}</td>
                <td style="font-weight: bold;">{{ $totals['fuel_consumption'] }}</td>
                <td style="font-weight: bold;">{{ $totals['days_worked'] }}</td>
                <td class="currency" style="font-weight: bold;">S/. {{ $totals['cost_per_hour'] }}</td>
                <td class="currency" style="font-weight: bold;">S/. {{ $totals['total_amount'] }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Sección Resumen -->
    <div class="summary-section">
        <div class="summary-title">RESUMEN</div>
        
        <table class="summary-table">
            <thead>
                <tr>
                    <th>HORAS<br>TRABAJADAS</th>
                    <th>HORA<br>EQUIVALENTE</th>
                    <th>COSTO<br>HM</th>
                    <th>IMPORTE A<br>PAGAR S/.</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="summary-value">{{ $totals['time_worked'] }}</td>
                    <td class="summary-value">{{ $totals['equivalent_hours'] }}</td>
                    <td class="summary-value currency">S/. {{ $totals['cost_per_hour'] }}</td>
                    <td class="summary-value currency">S/. {{ $totals['total_amount'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>