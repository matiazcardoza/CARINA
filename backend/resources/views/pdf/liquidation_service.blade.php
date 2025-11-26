<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidación de Servicio de Alquiler</title>
    <style>
        /* Reset básico optimizado para PDF */
        * {
            margin: 0px, 15mm, 0mm, 15mm;
            padding: 0;
            box-sizing: border-box;
        }

        /* Configuración general del documento - CAMBIO A LANDSCAPE */
        @page {
            margin: 3mm 15mm 0mm 15mm;
            size: A4 landscape; /* CAMBIO PRINCIPAL */
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
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
        .mb-20 { margin-bottom: 20px; }

        /* Header optimizado para horizontal */
        .header {
            width: 100%;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #000;
        }

        .header-table td {
            vertical-align: top;
            padding: 6px;
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
            padding: 0 15px;
        }

        .title-cell h1 {
            color: #0066cc;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .title-cell h2 {
            color: #000;
            font-size: 9px;
            font-weight: normal;
            margin: 1px 0;
        }

        .info-cell {
            width: 120px;
            text-align: right;
            vertical-align: top;
            padding-top: 0;
        }

        .anexo-box {
            border: 2px solid #0066cc;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 10px;
            color: #0066cc;
            margin-bottom: 6px;
            display: inline-block;
        }

        .fecha-box {
            border: 1px solid #0066cc;
            padding: 4px 6px;
            font-weight: bold;
            font-size: 9px;
            color: #0066cc;
            display: inline-block;
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

        /* Título del formulario - más compacto */
        .form-title {
            background-color: #4472C4;
            color: white;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 8px;
            margin: 12px 0;
            page-break-inside: avoid;
        }

        /* Layout principal en dos columnas para aprovechar el espacio horizontal */
        .main-content {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .left-column {
            flex: 1;
        }

        .right-column {
            flex: 1;
        }

        /* Información principal - más compacta */
        .info-section {
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .info-table td {
            padding: 6px 8px;
            vertical-align: middle;
            border: none;
        }

        .info-label {
            font-weight: bold;
            white-space: nowrap;
            width: 140px;
            font-size: 9px;
        }

        .info-value {
            border-bottom: 1px solid #000;
            padding-left: 8px;
            min-height: 20px;
            display: flex;
            align-items: center;
            font-size: 10px;
        }

        /* Sección de costos - layout horizontal */
        .cost-section {
            margin: 15px 0;
            background-color: #f9f9f9;
            padding: 12px;
            border: 1px solid #ddd;
        }

        .cost-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cost-table td {
            padding: 8px;
            vertical-align: middle;
        }

        .cost-label {
            font-weight: bold;
            width: 120px;
            font-size: 10px;
        }

        .cost-breakdown {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .cost-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .cost-item-label {
            font-weight: bold;
            white-space: nowrap;
            font-size: 9px;
        }

        .cost-value {
            border-bottom: 1px solid #000;
            padding: 3px 6px;
            min-width: 70px;
            text-align: center;
            font-weight: bold;
            font-family: monospace;
            font-size: 10px;
        }

        .total-label {
            font-weight: bold;
            font-size: 11px;
        }

        .total-value {
            border: 2px solid #000;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            background-color: #f0f0f0;
            font-family: monospace;
        }

        /* Observaciones - más compactas */
        .observations-section {
            margin: 15px 0;
        }

        .observations-label {
            font-weight: bold;
            margin-bottom: 6px;
            font-size: 10px;
        }

        .observations-box {
            border: 1px solid #000;
            min-height: 40px;
            padding: 8px;
        }

        /* Total final - layout horizontal */
        .total-final-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border: 2px solid #000;
        }

        .total-final-label {
            font-size: 14px;
            font-weight: bold;
        }

        .total-final-value {
            border: 3px solid #000;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            background-color: #fff;
            font-family: monospace;
        }

        /* Total en letras - más compacto */
        .total-words-section {
            margin: 15px 0;
            border: 2px solid #000;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .total-words-label {
            font-weight: bold;
            margin-bottom: 6px;
            font-size: 10px;
        }

        .total-words-value {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            text-align: center;
            padding: 3px;
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
            font-family: monospace;
            font-weight: bold;
        }

        /* Espaciado específico */
        .section-separator {
            margin: 15px 0;
        }

        /* Responsive para landscape */
        @media print {
            body {
                width: 297mm;
                height: 210mm;
            }
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
                    <h2>Oficina Regional de Administración</h2>
                    <h2>Oficina de Equipo Mecánico</h2>
                </td>

                <td class="info-cell">
                    <div class="anexo-box">ANEXO N°06</div>
                    <div class="fecha-box"><span class="info-line">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span></div>
                    <div class="numero-box">C-{{ $serviceId }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Form Title -->
    <div class="form-title">LIQUIDACIÓN DE SERVICIO DE ALQUILER</div>

    <!-- Contenido principal en dos columnas -->
    <div class="main-content">
        <!-- Columna izquierda -->
        <div class="left-column">
            <!-- Información principal -->
            <div class="info-section">
                <table class="info-table">
                    <tr>
                        <td class="info-label">USUARIO SOLICITANTE:</td>
                        <td class="info-value">SUB GERENCIA DE OBRAS</td>
                    </tr>
                </table>
                
                <table class="info-table">
                    <tr>
                        <td class="info-label">DIRECCIÓN:</td>
                        <td class="info-value">JR. MOQUEGUA N° 269-A</td>
                    </tr>
                </table>
                
                <table class="info-table">
                    <tr>
                        <td class="info-label">SERVICIO ALQUILADO:</td>
                        <td class="info-value">{{ $equipment['machinery_equipment'] . ' ' . $equipment['brand'] . ' ' . $equipment['model'] . ' ' . $equipment['plate'] }}</td>
                    </tr>
                </table>
            </div>

            <!-- Observaciones -->
            <div class="observations-section">
                <div class="observations-label">OBSERVACIONES:</div>
                <div class="observations-box">
                    <!-- Espacio para observaciones -->
                </div>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="right-column">
            <div class="info-section">
                <table class="info-table">
                    <tr>
                        <td class="info-label">PERIODO DE ALQUILER:</td>
                        <td class="info-value">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <span>DEL {{ $requestData['minDate'] }} AL {{ $requestData['maxDate'] }}</span>
                                <span style="font-weight: bold;">HORAS: {{ $requestData['minStartTime'] }} A {{ $requestData['maxEndTime'] }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <table class="info-table">
                    <tr>
                        <td class="info-label">TOTAL DIAS/HORAS:</td>
                        <td class="info-value">
                            <div style="display: flex; gap: 15px;">
                                <span><strong>Días:</strong> {{ $authData['totals']['days_worked'] }}</span>
                                <span><strong>Horas:</strong> {{ $authData['totals']['equivalent_hours'] }}</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Sección de costos -->
            <div class="cost-section">
                <table class="cost-table">
                    <tr>
                        <td class="cost-label">COSTO DEL SERVICIO:</td>
                        <td>
                            <div class="cost-breakdown">
                                <div class="cost-item">
                                    <span class="cost-item-label">POR DÍA:</span>
                                    <span class="cost-value currency">S/. {{ $liquidationData['cost_per_day'] }}</span>
                                </div>
                                <div class="cost-item">
                                    <span class="cost-item-label">POR HORA:</span>
                                    <span class="cost-value currency">S/. {{ $authData['totals']['cost_per_hour'] }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Total final destacado -->
    <div class="total-final-section">
        <span class="total-final-label">TOTAL A PAGAR:</span>
        <span class="total-final-value currency">S/. {{ $authData['totals']['total_amount'] }}</span>
    </div>

    <!-- Total en letras -->
    <div class="total-words-section">
        <div class="total-words-label">TOTAL (EN LETRAS):</div>
        <div class="total-words-value">
            {{ $liquidationData['total_in_words'] }}
        </div>
    </div>

</body>
</html>