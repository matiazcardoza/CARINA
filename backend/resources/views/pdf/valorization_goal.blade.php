<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuadro de Resumen Mensual - Valorización</title>
    <style>
        /* Reset básico optimizado para PDF */
        * {
            margin: 2px, 15mm, 2mm, 15mm;
            padding: 0;
            box-sizing: border-box;
        }

        /* Configuración general del documento */
        @page {
            margin: 2mm 10mm 2mm 10mm;
            size: A4 landscape;
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

        .form-subtitle {
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /* Tabla principal de valorización */
        .valoration-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
        }

        .valoration-table th,
        .valoration-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .valoration-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 7px;
            padding: 6px 3px;
            line-height: 1.1;
        }

        .valoration-table .item-col { width: 4%; }
        .valoration-table .maquinaria-col { width: 15%; }
        .valoration-table .operario-col { width: 18%; }
        .valoration-table .marca-col { width: 10%; }
        .valoration-table .placa-col { width: 8%; }
        .valoration-table .horas-col { width: 10%; }
        .valoration-table .precio-col { width: 10%; }
        .valoration-table .total-col { width: 10%; }
        .valoration-table .costo-dia-col { width: 8%; }
        .valoration-table .dias-col { width: 7%; }

        .valoration-table .total-row {
            background-color: #D9D9D9;
            font-weight: bold;
        }

        .valoration-table .total-row td {
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
            width: 50%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        .summary-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }

        .summary-table .summary-label {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
            width: 60%;
            text-align: left;
            padding-left: 10px;
        }

        .summary-table .summary-value {
            font-weight: bold;
            font-size: 10px;
            text-align: right;
            padding-right: 10px;
        }

        .summary-note {
            text-align: center;
            font-size: 8px;
            font-style: italic;
            margin-top: 10px;
        }

        /* Optimizaciones específicas para dompdf */
        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .currency {
            
        }

        .info-cell {
            width: 170px;
            text-align: right;
            vertical-align: top;
            padding-top: 0;
        }

        .fecha-box {
            border: 2px solid #0066cc;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 11px;
            color: #0066cc;
            display: inline-block;
            margin-top: 4px;
        }

        .numero-box {
            border: 2px solid #0066cc;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 11px;
            color: #0066cc;
            display: inline-block;
            margin-top: 4px;
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

                <td class="info-cell">
                    <div class="fecha-box"><span class="info-line">{{ \Carbon\Carbon::parse($record['created_at'])->format('d/m/Y') ?? \Carbon\Carbon::now()->format('d/m/Y') }}</span></div>
                    <div class="numero-box">C-{{ str_pad($record['num_reg'], 4, '0', STR_PAD_LEFT) ?? $serviceId }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Form Title -->
    <div class="form-title">ANEXO 1 - CUADRO DE RESUMEN MENSUAL - {{ $mes }}</div>
    <div style="text-align: center;">
        {{ $goalDetail }}
    </div>

    <div class="form-subtitle">REPORTE MENSUAL DE HORAS DE MAQUINARIAS GOBIERNO REGIONAL DE PUNO</div>

    <!-- Tabla principal de valorización -->
    <table class="valoration-table">
        <thead>
            <tr>
                <th class="item-col">ITEM</th>
                <th class="maquinaria-col">MAQUINARIA</th>
                <th class="operario-col">OPERARIO</th>
                <th class="marca-col">MARCA</th>
                <th class="placa-col">PLACA</th>
                <th class="horas-col">HORAS<br>TRABAJADAS POR<br>MÁQUINA</th>
                <th class="precio-col">PRECIO HORA<br>MÁQUINA</th>
                <th class="total-col">TOTAL EN<br>SOLES</th>
                <th class="costo-dia-col">COSTO POR<br>DÍA</th>
                <th class="dias-col">DÍAS<br>TRABAJADOS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($valorationData['machinery'] as $index => $machinery)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: left; padding-left: 5px;">
                        {{ $machinery['equipment']['machinery_equipment'] ?? 'N/A' }}
                        {{ $machinery['equipment']['model'] ?? '' }}
                    </td>
                    <td style="text-align: left; padding-left: 5px;">
                        @if(isset($machinery['equipment']['operators']) && count($machinery['equipment']['operators']) > 0)
                            @foreach($machinery['equipment']['operators'] as $key => $op)
                                {{ strtoupper($op['name']) }}@if($key < count($machinery['equipment']['operators']) - 1), @endif
                            @endforeach
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $machinery['equipment']['brand'] ?? 'N/A' }}</td>
                    <td>{{ $machinery['equipment']['plate'] ?? 'N/A' }}</td>
                    <td>{{ $machinery['equivalent_hours'] }}</td>
                    <td class="currency">{{ number_format($machinery['cost_per_hour'], 2) }}</td>
                    <td class="currency" style="font-weight: bold;">S/. {{ number_format($machinery['total_amount'], 2) }}</td>
                    <td class="currency">S/. {{ number_format($machinery['cost_per_day'], 2) }}</td>
                    <td>{{ number_format($machinery['days_worked'], 2) }}</td>
                </tr>
            @endforeach

            <!-- Fila de totales -->
            <tr class="total-row">
                <td colspan="7" style="text-align: right; padding-right: 10px; font-weight: bold;">TOTAL</td>
                <td class="currency" style="font-weight: bold;">S/. {{ number_format($valorationData['valoration_amount'], 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <!-- Sección Resumen -->
    <div class="summary-section">
        <div class="summary-title">RESUMEN</div>

        <table class="summary-table">
            <tbody>
                <tr>
                    <td class="summary-label">MONTO TOTAL DE VALORIZACIÓN</td>
                    <td class="summary-value currency">S/. {{ number_format($valorationData['valoration_amount'], 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label">Pago de operadores de maquinaria pesada del GRP, según Planilla</td>
                    <td class="summary-value currency">S/. {{ number_format($amountPlanilla ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="summary-label"><strong>TOTAL A PAGAR</strong></td>
                    <td class="summary-value currency"><strong><span>S/. {{ number_format($editedValorationAmount ?? $valorationData['valoration_amount'], 2) }}</span></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

</body>
</html>
