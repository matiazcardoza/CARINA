<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Movilidad</title>
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
            font-size: 10px;
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

        .numero-cell {
            width: 100px;
            text-align: center;
            vertical-align: middle;
        }

        .numero-box {
            border: 2px solid #0066cc;
            padding: 8px;
            display: inline-block;
            font-weight: bold;
            font-size: 12px;
            color: #0066cc;
        }

        /* Título del formulario */
        .form-title {
            background-color: #4472C4;
            color: white;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 8px;
            margin: 10px 0;
            page-break-inside: avoid;
        }

        /* Información básica */
        .info-table {
            width: 100%;
            margin-bottom: 0px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 6px 5px;
            vertical-align: middle;
            border: none;
        }

        .info-label {
            font-weight: bold;
            white-space: nowrap;
            width: 150px;
        }

        .info-line {
            border-bottom: 1px solid #000;
            min-height: 18px;
            display: inline-block;
            width: 100%;
            padding-left: 5px;
        }

        /* Tabla de fechas y horarios */
        .datetime-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .datetime-table th,
        .datetime-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            vertical-align: middle;
        }

        .datetime-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
        }

        .datetime-table .label-col {
            background-color: #E7E6E6;
            font-weight: bold;
            width: 15%;
            text-align: center;
        }

        .datetime-table .value-col {
            width: 35%;
            text-align: center;
        }

        /* Personal comisionado */
        .personal-section {
            margin-bottom: 15px;
        }

        .personal-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .personal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .personal-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
            min-height: 25px;
        }

        .personal-table .numero-col {
            width: 10%;
            text-align: center;
            font-weight: bold;
        }

        .personal-table .nombre-col {
            width: 90%;
            text-align: left;
        }

        /* Información de vehículo */
        .vehicle-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .vehicle-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }

        .vehicle-label {
            background-color: #E7E6E6;
            font-weight: bold;
            width: 25%;
            text-align: center;
        }

        .vehicle-value {
            width: 75%;
            text-align: center;
        }

        /* Kilometraje */
        .km-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .km-table th,
        .km-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
        }

        .km-table th {
            background-color: #E7E6E6;
            font-weight: bold;
            font-size: 9px;
        }

        .km-table .km-col {
            width: 33.33%;
        }

        /* Observaciones */
        .observations {
            margin-bottom: 20px;
        }

        .observations-label {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .observations-box {
            border: 1px solid #000;
            min-height: 60px;
            padding: 8px;
        }

        /* Firmas optimizadas */
        .signatures-section {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signatures-table td {
            width: 33.33%;
            padding: 20px 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 40px;
            margin-bottom: 8px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 9px;
        }

        /* Separador */
        .separator {
            border-bottom: 1px dashed #000;
            margin: 20px 0;
            height: 1px;
            overflow: hidden;
        }

        .separator::after {
            content: "…………………………………………………………………………………………………………………………………………………………………";
            display: block;
            color: #000;
            font-size: 8px;
            line-height: 1;
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

                <td class="numero-cell">
                    <div class="numero-box">C-{{ $service->id }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Form Title -->
    <div class="form-title">SOLICITUD DE MOVILIDAD</div>

    <!-- Información básica -->
    <table class="info-table">
        <tr>
            <td class="info-label">UNIDAD ÓRGANICA SOLICITANTE:</td>
            <td>
                <span class="info-line">SUB GERENCIA DE OBRAS</span>
            </td>
        </tr>
        <tr>
            <td class="info-label">FECHA:</td>
            <td>
                <span class="info-line">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </td>
        </tr>
    </table>

    <!-- Objetivo y Destino -->
    <table class="info-table" style="margin-bottom: 5px;">
        <tr>
            <td class="info-label">OBJETIVO DE LA COMISION:</td>
            <td>
                <span class="info-line">{{ $service->goal_detail }}</span>
            </td>
        </tr>
        <tr>
            <td class="info-label">DESTINO:</td>
            <td>
                <span class="info-line">{{ $service->goal_detail }}</span>
            </td>
        </tr>
    </table>

    <!-- Duración y Horarios -->
    <table class="datetime-table">
        <thead>
            <tr>
                <th rowspan="2" class="label-col">DURACIÓN</th>
                <th style="width: 35%;">DEL:</th>
                <th style="width: 35%;">AL:</th>
                <th rowspan="2" class="label-col">HORA</th>
            </tr>
            <tr>
                <td>{{ $minDate }}</td>
                <td>{{ $maxDate }}</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-col">Salida:</td>
                <td>7:00 a.m.</td>
                <td class="label-col">Retorno:</td>
                <td>5:00 p.m.</td>
            </tr>
        </tbody>
    </table>

    <!-- Personal Comisionado -->
    <div class="personal-section">
        <div class="personal-label">PERSONAL COMISIONADO:</div>
        <table class="personal-table">
            <tr>
                <td class="numero-col">1.-</td>
                <td class="nombre-col">-</td>
            </tr>
            <tr>
                <td class="numero-col">2.-</td>
                <td class="nombre-col">-</td>
            </tr>
        </table>
    </div>

    <!-- Información del Vehículo -->
    <table class="vehicle-table">
        <tr>
            <td class="vehicle-label">MOVILIDAD / PLACA:</td>
            <td class="vehicle-value">{{ $service->description }}</td>
        </tr>
        <tr>
            <td class="vehicle-label">CHOFER / OPERARIO:</td>
            <td class="vehicle-value">{{ $service->operator }}</td>
        </tr>
    </table>

    <!-- Kilometraje -->
    <table class="km-table">
        <thead>
            <tr>
                <th class="km-col">KILOMETRAJE INICIAL</th>
                <th class="km-col">KILOMETRAJE RECORRIDO</th>
                <th class="km-col">KILOMETRAJE FINAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="height: 30px;"></td>
                <td style="height: 30px;"></td>
                <td style="height: 30px;"></td>
            </tr>
        </tbody>
    </table>

    <!-- Observaciones -->
    <div class="observations">
        <div class="observations-label">OBSERVACIONES:</div>
        <div class="observations-box"></div>
    </div>

    <!-- Firmas -->
    <div class="signatures-section">
        <table class="signatures-table">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">RESIDENTE DE OBRA</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">SUPERVISOR DE OBRA</div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-title">FIRMA EQUIPO MECÁNICO</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Separador con puntos -->
    <div class="separator"></div>

</body>
</html>