<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Parte Diario de Trabajo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .section {
            margin-bottom: 15px;
            border: 1px solid #ccc;
            padding: 10px;
        }
        
        .section-title {
            background-color: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px;
            margin: -10px -10px 10px -10px;
            border-bottom: 1px solid #ccc;
        }
        
        .row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .col-50 {
            width: 48%;
            margin-right: 2%;
        }
        
        .col-33 {
            width: 31%;
            margin-right: 2%;
        }
        
        .col-25 {
            width: 23%;
            margin-right: 2%;
        }
        
        .field {
            margin-bottom: 8px;
        }
        
        .field-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        
        .field-value {
            border-bottom: 1px solid #333;
            display: inline-block;
            min-width: 150px;
            padding-left: 5px;
        }
        
        .activities-list {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 80px;
            background-color: #fafafa;
        }
        
        .activities-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .observations {
            border: 1px solid #ccc;
            padding: 10px;
            min-height: 50px;
            background-color: #fafafa;
        }
        
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .signatures-table th,
        .signatures-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }
        
        .signatures-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .signature-box {
            height: 60px;
            border-bottom: 1px solid #333;
            margin-top: 20px;
            position: relative;
        }
        
        .signature-label {
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10px;
            text-align: center;
        }
        
        .evidences-list {
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #fafafa;
        }
        
        .evidences-list ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            text-transform: uppercase;
        }
        
        .status-firmado {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        @media print {
            body { margin: 0; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <!-- Encabezado -->
    <div class="header">
        <h1>PARTE DIARIO DE TRABAJO</h1>
        <h2>CONTROL DE MAQUINARIA Y EQUIPOS</h2>
    </div>
    
    <div class="company-info">
        <strong>{{ $reportData['empresa'] }}</strong><br>
        {{ $reportData['proyecto'] }}<br>
        Contrato: {{ $reportData['contrato'] }}
    </div>

    <!-- Información General -->
    <div class="section">
        <div class="section-title">Información General</div>
        <div class="row">
            <div class="col-50">
                <div class="field">
                    <span class="field-label">Fecha:</span>
                    <span class="field-value">{{ $dailyPartData['fecha_parte'] }}</span>
                </div>
                <div class="field">
                    <span class="field-label">Equipo:</span>
                    <span class="field-value">{{ $dailyPartData['servicio']['nombre'] }}</span>
                </div>
            </div>
            <div class="col-50">
                <div class="field">
                    <span class="field-label">Código:</span>
                    <span class="field-value">{{ $dailyPartData['servicio']['codigo'] }}</span>
                </div>
                <div class="field">
                    <span class="field-label">Operador:</span>
                    <span class="field-value">{{ $dailyPartData['servicio']['operador'] }}</span>
                </div>
            </div>
        </div>
        <div class="field">
            <span class="field-label">Proyecto:</span>
            <span class="field-value" style="min-width: 400px;">{{ $dailyPartData['servicio']['proyecto'] }}</span>
        </div>
    </div>

    <!-- Control Horario -->
    <div class="section">
        <div class="section-title">Control de Horario</div>
        <div class="row">
            <div class="col-25">
                <div class="field">
                    <span class="field-label">Inicio:</span>
                    <span class="field-value">{{ $dailyPartData['horario']['hora_inicio'] }}</span>
                </div>
            </div>
            <div class="col-25">
                <div class="field">
                    <span class="field-label">Fin:</span>
                    <span class="field-value">{{ $dailyPartData['horario']['hora_fin'] }}</span>
                </div>
            </div>
            <div class="col-25">
                <div class="field">
                    <span class="field-label">H. Total:</span>
                    <span class="field-value">{{ $dailyPartData['horario']['horas_trabajadas'] }}</span>
                </div>
            </div>
            <div class="col-25">
                <div class="field">
                    <span class="field-label">H. Efectivas:</span>
                    <span class="field-value">{{ $dailyPartData['horario']['horas_efectivas'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Control de Combustible -->
    <div class="section">
        <div class="section-title">Control de Combustible</div>
        <div class="row">
            <div class="col-25">
                <div class="field">
                    <span class="field-label">Inicial (L):</span>
                    <span class="field-value">{{ $dailyPartData['combustible']['inicial'] }}</span>
                </div>
            </div>
            <div class="col-25">
                <div class="field">
                    <span class="field-label">Final (L):</span>
                    <span class="field-value">{{ $dailyPartData['combustible']['final'] }}</span>
                </div>
            </div>
            <div class="col-25">
                <div class="field">
                    <span class="field-label">Consumido (L):</span>
                    <span class="field-value">{{ $dailyPartData['combustible']['consumido'] }}</span>
                </div>
            </div>
            <div class="col-25">
                <div class="field">
                    <span class="field-label">Rendimiento:</span>
                    <span class="field-value">{{ $dailyPartData['combustible']['rendimiento'] }} L/h</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividades Realizadas -->
    <div class="section">
        <div class="section-title">Actividades Realizadas</div>
        <div class="activities-list">
            <ul>
                @foreach($dailyPartData['actividades'] as $actividad)
                    <li>{{ $actividad }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Observaciones -->
    <div class="section">
        <div class="section-title">Observaciones</div>
        <div class="observations">
            {{ $dailyPartData['observaciones'] }}
        </div>
    </div>

    <!-- Evidencias -->
    <div class="section">
        <div class="section-title">Evidencias Fotográficas</div>
        <div class="evidences-list">
            <ul>
                @foreach($dailyPartData['evidencias'] as $evidencia)
                    <li>{{ $evidencia }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Control de Firmas -->
    <div class="section">
        <div class="section-title">Control de Firmas Digitales</div>
        <table class="signatures-table">
            <thead>
                <tr>
                    <th>Nivel</th>
                    <th>Responsable</th>
                    <th>Fecha/Hora</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dailyPartData['firmas'] as $firma)
                    <tr>
                        <td>{{ $firma['nivel'] }}</td>
                        <td>{{ $firma['nombre'] }}</td>
                        <td>{{ $firma['fecha'] ?? 'Pendiente' }}</td>
                        <td>
                            <span class="status-badge status-{{ $firma['estado'] }}">
                                {{ $firma['estado'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pie de página -->
    <div class="footer">
        <strong>Sistema de Gestión de Partes Diarios</strong><br>
        Generado el: {{ $reportData['fecha_generacion'] }} por {{ $reportData['usuario_genera'] }}<br>
        Documento ID: PD-{{ str_pad($dailyPartData['id'], 6, '0', STR_PAD_LEFT) }}
    </div>
</body>
</html>