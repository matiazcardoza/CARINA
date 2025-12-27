<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Deductivos</title>
    <style>
        * {
            margin: 2px, 15px, 15px, 15px;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 2mm 15mm 12mm 15mm;
            size: A4 portrait;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h2 {
            font-size: 11pt;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .header h3 {
            font-size: 10pt;
            margin-bottom: 3px;
        }

        .section-divider {
            margin: 30px 0 20px 0;
            padding: 10px;
            background-color: #e74c3c;
            color: white;
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
        }

        .mes-section, .order-section {
            margin-bottom: 25px;
        }

        .mes-title {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        .order-title {
            background-color: #16a085;
            color: white;
            padding: 8px;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th {
            background-color: #34495e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            border: 1px solid #2c3e50;
        }

        table td {
            padding: 6px 8px;
            border: 1px solid #bdc3c7;
            font-size: 9pt;
        }

        table tbody tr:nth-child(even) {
            background-color: #ecf0f1;
        }

        table tbody tr:hover {
            background-color: #d5dbdb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            background-color: #3498db !important;
            color: white;
            font-weight: bold;
        }

        .total-row td {
            border-color: #2980b9;
        }

        .subtotal-section {
            margin: 15px 0;
            padding: 10px;
            background-color: #3498db;
            color: white;
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
        }

        .total-general {
            margin-top: 20px;
            text-align: right;
            font-size: 12pt;
            font-weight: bold;
            padding: 10px;
            background-color: #27ae60;
            color: white;
            border-radius: 5px;
        }

        .page-break {
            page-break-after: always;
        }

        .footer {
            position: fixed;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>RESUMEN DE DEDUCTIVOS</h2>
        <h4>{{ $service['goal_detail'] }}</h4>
    </div>

    {{-- SECCIÓN DE PLANILLAS --}}
    @if($tienePlanillas)
        <div class="section-divider">
            DEDUCTIVOS DE PLANILLAS
        </div>

        @foreach($deductivosPorMes as $mesData)
            <div class="mes-section">
                <div class="mes-title">
                    MES DE {{ $mesData['nombreMes'] }} DEL {{ \Carbon\Carbon::parse($record['created_at'])->year }}
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">ITEM</th>
                            <th style="width: 35%;">NOMBRES Y APELLIDOS</th>
                            <th style="width: 35%;">CARGO</th>
                            <th style="width: 25%;" class="text-right">PAGO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mesData['items'] as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ strtoupper($item['nombres']) }} {{ strtoupper($item['apellidos']) }}</td>
                                <td>{{ strtoupper($item['cargo']) }}</td>
                                <td class="text-right">S/ {{ number_format($item['montoPago'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="3" class="text-right"><strong>TOTAL MES {{ $mesData['nombreMes'] }}</strong></td>
                            <td class="text-right"><strong>S/ {{ number_format($mesData['total'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="subtotal-section">
            TOTAL PLANILLAS: S/ {{ number_format($totalPlanillas, 2) }}
        </div>

        {{-- SALTO DE PÁGINA SI HAY ÓRDENES --}}
        @if($tieneOrdenes)
            <div class="page-break"></div>
        @endif
    @endif

    {{-- SECCIÓN DE ÓRDENES --}}
    @if($tieneOrdenes)
        {{-- Repetir header en la nueva página --}}
        <div class="header">
            <h2>RESUMEN DE DEDUCTIVOS</h2>
            <h4>{{ $service['goal_detail'] }}</h4>
        </div>

        <div class="section-divider">
            DEDUCTIVOS DE ÓRDENES
        </div>

        @foreach($deductivosOrdenes as $orden)
            <div class="order-section">
                <div class="order-title">
                    ORDEN N° {{ $orden['numero'] }} - {{ $orden['rsocial'] }} (RUC: {{ $orden['ruc'] }})
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">ITEM</th>
                            <th style="width: 70%;">DESCRIPCIÓN</th>
                            <th style="width: 25%;" class="text-right">MONTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orden['items'] as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ strtoupper($item['item']) }}</td>
                                <td class="text-right">S/ {{ number_format($item['monto'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="2" class="text-right"><strong>TOTAL ORDEN {{ $orden['numero'] }}</strong></td>
                            <td class="text-right"><strong>S/ {{ number_format($orden['total'], 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="subtotal-section">
            TOTAL ÓRDENES: S/ {{ number_format($totalOrdenes, 2) }}
        </div>
    @endif
</body>
</html>