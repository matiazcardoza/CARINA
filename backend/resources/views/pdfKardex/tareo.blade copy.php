<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tareo</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1cm 0.5cm 4.5cm 0.5cm;
        }

        html {
            width: 100%;
        }

        body {
            margin: 10px;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: <?= isset($pdf) && $pdf == true ? '0.4rem' : '0.6rem' ?>;
        }

        header {
            width: 100%;
            position: fixed;
            display: flex;
            justify-content: space-between;
            top: -0.75cm;
            left: 0cm;
        }

        footer {
            width: 100%;
            position: fixed;
            display: flex;
            justify-content: space-between;
            bottom: -4.30cm;
            left: 0cm;
        }

        .h1 {
            text-align: center;
            color: #333;
            font-size: <?= isset($pdf) && $pdf == true ? '0.7rem' : '1rem' ?>;
        }

        .h2 {
            text-align: center;
            color: #333;
            font-size: <?= isset($pdf) && $pdf == true ? '0.6rem' : '0.8rem' ?>;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #ddd;
            padding: <?= isset($pdf) && $pdf == true ? '1px' : '3px' ?>;
        }

        .tabla th {
            background-color: #f0f0f0;
            text-align: right;
        }

        .tabla td {
            text-align: right;
        }

        .footer-table {
            width: 100%;
            margin-top: 16px;
            padding-top: 6px;
            font-size: 0.4rem;
        }

        .footer-table td {
            padding: 6px;
        }

        .qr-container img {
            height: 70px;
            width: 70px;
        }

        .pagenum:before {
            content: "Página " counter(page);
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    <header style="width: 100%;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 30%; display:flex; justify-content:start; align-items: center;">
                    <div>
                        @if($empresa->logo)
                        <img src="storage/{{ $empresa->logo }}" alt="" width="40px" height="40px">
                        @endif
                    </div>
                </td>
                <td style="width: 70%;">
                    <div style="text-align: right;">
                        <span style="text-align: right; font-weight: bold">{{ $empresa->razon_social }}</span>
                        <br>
                        <span style="text-align: right;">RUC: {{ $empresa->ruc }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <footer style="width: 100%;">
        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td style="width: 14%; border-right: 1px solid gray; text-align: justify; vertical-align: bottom; height: 100px;">
                        <div class="pagenum" style="font-size: 12px;"></div>
                    </td>
                    <td style="width: 56%; border-right: 1px solid gray; text-align: justify;">
                        <p>SEGÚN RESOLUCIÓN GERENCIAL GENERAL REGIONAL N° 627-2018-GGR- PUNO, QUE APRUEBA LA DIRECTIVA REGIONAL N° 10-2018.
                            En el punto 8.1.4.2 ejecucion fisica del proyecto de inversion literal "e" indica: es responsable del personal
                            que labora en {{$proyecto}}</p>
                        <p>El V°B° del GERENTE Y SUB GERENTE , solo es para efectos de tramite administrativo.BASADO EN LOS PRINCIPIOS DE CONFIANZA</p>
                    </td>
                    <td style="width: 26%; border-right: 1px solid gray; text-align: justify;">
                        <p>Esta es una representación impresa cuya autenticidad puede ser contrastada
                            con la representación imprimible localizada en la sede digital del Gobierno Regional Puno, aplicando
                            lo dispuesto por el Art. 25 de D.S. 070–2013-PCM y la Tercera Disposición Complementaria Final del
                            D.S. 026-2016-PCM. Su autenticidad e integridad pueden ser contrastadas a través de este QR:</p>
                    </td>
                    <td class="qr-container" style="width: 4%;">
                        @if($excel == false)
                        <img src="data:image/png;base64,{{$qr_code}}" alt="Código QR" style="height: 70px; width: 70px;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </footer>


    @if(count($personal) != 0)
    <div>
        <table class="tabla">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            <span class="h1" style="font-weight: bold;">HOJA DE TAREO</span>
                            <br>
                            <span class="h2">{{$tipo->nombre}}</span>
                            <br>
                            <span class="h2" style="font-weight: bold;">{{$tipo_asistencia}}</span>
                        </div>
                    </td>
                    <th colspan="2" style="text-align: center; font-weight: bold; width: 70%;">
                        <span class="h2">{{ $empresa->razon_social }}</span>
                    </th>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            @if($empresa->logo)
                            <img src="storage/{{ $empresa->logo }}" alt="" width="70px" height="70px">
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    @if($oficina->id == 49 || $oficina->id == 48)
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">GERENCIA REGIONAL DE INFRAESTRUCTURA</th>
                    @else
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                    @endif
                </tr>
                <tr>
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                </tr>
                <tr>
                    <td colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $proyecto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <tbody>
                <tr>
                    <th colspan="3" style="text-align: center; font-weight: bold; width: 50%;">RESPONSABLES:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">CUI:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">META:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">PLAZO DE EJECUCIÓN:</th>
                </tr>

                @if($tipo->codigo == 'GASGES')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">OFICINA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @elseif($tipo->codigo == 'EXPTEC')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">JEFE DE PROYECTO:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $jefe_proyecto?->nombre_completo }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @else
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">{{ $supervisor ? 'SUPERVISOR DE OBRA:' : 'INSPECTOR DE OBRA:' }}</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $supervisor?->nombre_completo ?? $inspector?->nombre_completo }}</td>
                    <td rowspan="2" style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td rowspan="2" style="text-align: left;">{{$meta}}</td>
                    <td rowspan="2" style="text-align: left;"></td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">RESIDENTE DE OBRA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $residente?->nombre_completo }}</td>
                </tr>
                @endif

                <tr>
                    <td colspan="3" style="text-align: center; font-weight: bold;">PLIEGO: {{ $proyecto_completo->pliego }}</td>
                    <td colspan="3" style="text-align: center; font-weight: bold;">FUENTE DE FINANCIAMIENTO: {{ $tareo->fte_fto ?? $proyecto_completo->fte_fto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <thead>
                <tr>
                    <th rowspan="3" style="text-align: center;">N°</th>
                    <th rowspan="3" style="text-align: center;">Num. Doc. (DNI)</th>
                    <th rowspan="3" colspan="3" style="text-align: center;">Nombres y Apellidos</th>
                    <th rowspan="3" style="text-align: center;">F. Nacimiento</th>
                    <th rowspan="3" style="text-align: center;">Cargo</th>
                    <th colspan="{{ count($cabeceras[0]['nombre_dias']) + 1 }}" style="text-align: center; text-transform: uppercase;">{{ $mes }} del {{ $anio }}</th>
                </tr>
                <tr>
                    @foreach($cabeceras[0]['nombre_dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">TOTAL</th>
                </tr>
                <tr>
                    @foreach($cabeceras[0]['dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">DIAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($personal as $orden => $item)
                <tr>
                    <td style="text-align: center;">{{$orden+1}}</td>
                    <td style="text-align: left;">{{$item['num_doc']}}</td>
                    <td style="text-align: left;">{{$item['nombres']}}</td>
                    <td style="text-align: left;">{{$item['ap_paterno']}}</td>
                    <td style="text-align: left;">{{$item['ap_materno']}}</td>
                    <td style="text-align: left;">{{$item['fec_nacimiento']}}</td>
                    <td style="text-align: left;">{{$item['cargo']}}</td>
                    @foreach($item['asistencias'] as $a => $aitem)
                    @if($aitem == 'F')
                    <td style="text-align: center; background-color: #E1E1E1;">{{$aitem}}</td>
                    @else
                    <td style="text-align: center;">{{$aitem}}</td>
                    @endif

                    @endforeach
                    <td style="text-align: center;">{{$item['tot_asis']}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(count($r1_personal) != 0)
    <div class="page-break">
        <table class="tabla">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            <span class="h1" style="font-weight: bold;">HOJA DE TAREO</span>
                            <br>
                            <span class="h2">{{$tipo->nombre}}</span>
                            <br>
                            <span class="h2" style="font-weight: bold;">{{$r1_tipo_asistencia}}</span>
                        </div>
                    </td>
                    <th colspan="2" style="text-align: center; font-weight: bold; width: 70%;">
                        <span class="h2">{{ $empresa->razon_social }}</span>
                    </th>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            @if($empresa->logo)
                            <img src="storage/{{ $empresa->logo }}" alt="" width="70px" height="70px">
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    @if($oficina->id == 49 || $oficina->id == 48)
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">GERENCIA REGIONAL DE INFRAESTRUCTURA</th>
                    @else
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                    @endif
                </tr>
                <tr>
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                </tr>
                <tr>
                    <td colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $proyecto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <tbody>
                <tr>
                    <th colspan="3" style="text-align: center; font-weight: bold; width: 50%;">RESPONSABLES:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">CUI:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">META:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">PLAZO DE EJECUCIÓN:</th>
                </tr>

                @if($tipo->codigo == 'GASGES')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">OFICINA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @elseif($tipo->codigo == 'EXPTEC')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">JEFE DE PROYECTO:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r1_jefe_proyecto?->nombre_completo }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @else
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">{{ $r1_supervisor ? 'SUPERVISOR DE OBRA:' : 'INSPECTOR DE OBRA:' }}</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r1_supervisor?->nombre_completo ?? $r1_inspector?->nombre_completo }}</td>
                    <td rowspan="2" style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td rowspan="2" style="text-align: left;">{{$meta}}</td>
                    <td rowspan="2" style="text-align: left;"></td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">RESIDENTE DE OBRA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r1_residente?->nombre_completo }}</td>
                </tr>
                @endif

                <tr>
                    <td colspan="3" style="text-align: center; font-weight: bold;">PLIEGO: {{ $proyecto_completo->pliego }}</td>
                    <td colspan="3" style="text-align: center; font-weight: bold;">FUENTE DE FINANCIAMIENTO: {{ $tareo->fte_fto ?? $proyecto_completo->fte_fto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <thead>
                <tr>
                    <th rowspan="3" style="text-align: center;">N°</th>
                    <th rowspan="3" style="text-align: center;">Num. Doc. (DNI)</th>
                    <th rowspan="3" colspan="3" style="text-align: center;">Nombres y Apellidos</th>
                    <th rowspan="3" style="text-align: center;">F. Nacimiento</th>
                    <th rowspan="3" style="text-align: center;">Cargo</th>
                    <th colspan="{{ count($r1_cabeceras[0]['nombre_dias']) + 1 }}" style="text-align: center; text-transform: uppercase;">{{ $r1_mes }} del {{ $r1_anio }}</th>
                </tr>
                <tr>
                    @foreach($r1_cabeceras[0]['nombre_dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">TOTAL</th>
                </tr>
                <tr>
                    @foreach($r1_cabeceras[0]['dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">DIAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($r1_personal as $orden => $item)
                <tr>
                    <td style="text-align: center;">{{$orden+1}}</td>
                    <td style="text-align: left;">{{$item['num_doc']}}</td>
                    <td style="text-align: left;">{{$item['nombres']}}</td>
                    <td style="text-align: left;">{{$item['ap_paterno']}}</td>
                    <td style="text-align: left;">{{$item['ap_materno']}}</td>
                    <td style="text-align: left;">{{$item['fec_nacimiento']}}</td>
                    <td style="text-align: left;">{{$item['cargo']}}</td>
                    @foreach($item['asistencias'] as $a => $aitem)
                    @if($aitem == 'F')
                    <td style="text-align: center; background-color: #E1E1E1;">{{$aitem}}</td>
                    @else
                    <td style="text-align: center;">{{$aitem}}</td>
                    @endif

                    @endforeach
                    <td style="text-align: center;">{{$item['tot_asis']}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(count($r2_personal) != 0)
    <div class="page-break">
        <table class="tabla">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            <span class="h1" style="font-weight: bold;">HOJA DE TAREO</span>
                            <br>
                            <span class="h2">{{$tipo->nombre}}</span>
                            <br>
                            <span class="h2" style="font-weight: bold;">{{$r2_tipo_asistencia}}</span>
                        </div>
                    </td>
                    <th colspan="2" style="text-align: center; font-weight: bold; width: 70%;">
                        <span class="h2">{{ $empresa->razon_social }}</span>
                    </th>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            @if($empresa->logo)
                            <img src="storage/{{ $empresa->logo }}" alt="" width="70px" height="70px">
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    @if($oficina->id == 49 || $oficina->id == 48)
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">GERENCIA REGIONAL DE INFRAESTRUCTURA</th>
                    @else
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                    @endif
                </tr>
                <tr>
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                </tr>
                <tr>
                    <td colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $proyecto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <tbody>
                <tr>
                    <th colspan="3" style="text-align: center; font-weight: bold; width: 50%;">RESPONSABLES:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">CUI:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">META:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">PLAZO DE EJECUCIÓN:</th>
                </tr>

                @if($tipo->codigo == 'GASGES')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">OFICINA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @elseif($tipo->codigo == 'EXPTEC')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">JEFE DE PROYECTO:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r2_jefe_proyecto?->nombre_completo }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @else
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">{{ $r2_supervisor ? 'SUPERVISOR DE OBRA:' : 'INSPECTOR DE OBRA:' }}</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r2_supervisor?->nombre_completo ?? $r2_inspector?->nombre_completo }}</td>
                    <td rowspan="2" style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td rowspan="2" style="text-align: left;">{{$meta}}</td>
                    <td rowspan="2" style="text-align: left;"></td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">RESIDENTE DE OBRA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r2_residente?->nombre_completo }}</td>
                </tr>
                @endif

                <tr>
                    <td colspan="3" style="text-align: center; font-weight: bold;">PLIEGO: {{ $proyecto_completo->pliego }}</td>
                    <td colspan="3" style="text-align: center; font-weight: bold;">FUENTE DE FINANCIAMIENTO: {{ $tareo->fte_fto ?? $proyecto_completo->fte_fto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <thead>
                <tr>
                    <th rowspan="3" style="text-align: center;">N°</th>
                    <th rowspan="3" style="text-align: center;">Num. Doc. (DNI)</th>
                    <th rowspan="3" colspan="3" style="text-align: center;">Nombres y Apellidos</th>
                    <th rowspan="3" style="text-align: center;">F. Nacimiento</th>
                    <th rowspan="3" style="text-align: center;">Cargo</th>
                    <th colspan="{{ count($r2_cabeceras[0]['nombre_dias']) + 1 }}" style="text-align: center; text-transform: uppercase;">{{ $r2_mes }} del {{ $r2_anio }}</th>
                </tr>
                <tr>
                    @foreach($r2_cabeceras[0]['nombre_dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">TOTAL</th>
                </tr>
                <tr>
                    @foreach($r2_cabeceras[0]['dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">DIAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($r2_personal as $orden => $item)
                <tr>
                    <td style="text-align: center;">{{$orden+1}}</td>
                    <td style="text-align: left;">{{$item['num_doc']}}</td>
                    <td style="text-align: left;">{{$item['nombres']}}</td>
                    <td style="text-align: left;">{{$item['ap_paterno']}}</td>
                    <td style="text-align: left;">{{$item['ap_materno']}}</td>
                    <td style="text-align: left;">{{$item['fec_nacimiento']}}</td>
                    <td style="text-align: left;">{{$item['cargo']}}</td>
                    @foreach($item['asistencias'] as $a => $aitem)
                    @if($aitem == 'F')
                    <td style="text-align: center; background-color: #E1E1E1;">{{$aitem}}</td>
                    @else
                    <td style="text-align: center;">{{$aitem}}</td>
                    @endif

                    @endforeach
                    <td style="text-align: center;">{{$item['tot_asis']}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(count($r3_personal) != 0)
    <div class="page-break">
        <table class="tabla">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            <span class="h1" style="font-weight: bold;">HOJA DE TAREO</span>
                            <br>
                            <span class="h2">{{$tipo->nombre}}</span>
                            <br>
                            <span class="h2" style="font-weight: bold;">{{$r3_tipo_asistencia}}</span>
                        </div>
                    </td>
                    <th colspan="2" style="text-align: center; font-weight: bold; width: 70%;">
                        <span class="h2">{{ $empresa->razon_social }}</span>
                    </th>
                    <td style="text-align: center; width: 15%;" rowspan="4">
                        <div>
                            @if($empresa->logo)
                            <img src="storage/{{ $empresa->logo }}" alt="" width="70px" height="70px">
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    @if($oficina->id == 49 || $oficina->id == 48)
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">GERENCIA REGIONAL DE INFRAESTRUCTURA</th>
                    @else
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                    @endif
                </tr>
                <tr>
                    <th colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</th>
                </tr>
                <tr>
                    <td colspan="2" class="h2" style="text-align: center; font-weight: bold;">{{ $proyecto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <tbody>
                <tr>
                    <th colspan="3" style="text-align: center; font-weight: bold; width: 50%;">RESPONSABLES:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">CUI:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">META:</th>
                    <th style="text-align: center; font-weight: bold; width: 16.5%;">PLAZO DE EJECUCIÓN:</th>
                </tr>

                @if($tipo->codigo == 'GASGES')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">OFICINA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $oficina->nombre }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @elseif($tipo->codigo == 'EXPTEC')
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">JEFE DE PROYECTO:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r3_jefe_proyecto?->nombre_completo }}</td>
                    <td style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td style="text-align: left;">{{$meta}}</td>
                    <td style="text-align: left;"></td>
                </tr>
                @else
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">{{ $r3_supervisor ? 'SUPERVISOR DE OBRA:' : 'INSPECTOR DE OBRA:' }}</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r3_supervisor?->nombre_completo ?? $r3_inspector?->nombre_completo }}</td>
                    <td rowspan="2" style="text-align: left;">{{$proyecto_completo->cui}}</td>
                    <td rowspan="2" style="text-align: left;">{{$meta}}</td>
                    <td rowspan="2" style="text-align: left;"></td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: bold; min-width: 150px;">RESIDENTE DE OBRA:</td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">{{ $r3_residente?->nombre_completo }}</td>
                </tr>
                @endif

                <tr>
                    <td colspan="3" style="text-align: center; font-weight: bold;">PLIEGO: {{ $proyecto_completo->pliego }}</td>
                    <td colspan="3" style="text-align: center; font-weight: bold;">FUENTE DE FINANCIAMIENTO: {{ $tareo->fte_fto ?? $proyecto_completo->fte_fto }}</td>
                </tr>
            </tbody>
        </table>

        <table class="tabla">
            <thead>
                <tr>
                    <th rowspan="3" style="text-align: center;">N°</th>
                    <th rowspan="3" style="text-align: center;">Num. Doc. (DNI)</th>
                    <th rowspan="3" colspan="3" style="text-align: center;">Nombres y Apellidos</th>
                    <th rowspan="3" style="text-align: center;">F. Nacimiento</th>
                    <th rowspan="3" style="text-align: center;">Cargo</th>
                    <th colspan="{{ count($r3_cabeceras[0]['nombre_dias']) + 1 }}" style="text-align: center; text-transform: uppercase;">{{ $r3_mes }} del {{ $r3_anio }}</th>
                </tr>
                <tr>
                    @foreach($r3_cabeceras[0]['nombre_dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">TOTAL</th>
                </tr>
                <tr>
                    @foreach($r3_cabeceras[0]['dias'] as $o => $item)
                    <th style="text-align: center;">{{$item}}</th>
                    @endforeach
                    <th style="text-align: center;">DIAS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($r3_personal as $orden => $item)
                <tr>
                    <td style="text-align: center;">{{$orden+1}}</td>
                    <td style="text-align: left;">{{$item['num_doc']}}</td>
                    <td style="text-align: left;">{{$item['nombres']}}</td>
                    <td style="text-align: left;">{{$item['ap_paterno']}}</td>
                    <td style="text-align: left;">{{$item['ap_materno']}}</td>
                    <td style="text-align: left;">{{$item['fec_nacimiento']}}</td>
                    <td style="text-align: left;">{{$item['cargo']}}</td>
                    @foreach($item['asistencias'] as $a => $aitem)
                    @if($aitem == 'F')
                    <td style="text-align: center; background-color: #E1E1E1;">{{$aitem}}</td>
                    @else
                    <td style="text-align: center;">{{$aitem}}</td>
                    @endif

                    @endforeach
                    <td style="text-align: center;">{{$item['tot_asis']}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($pdf == true)
    <div style="position: absolute; bottom: -2.30cm;; width: 100%;">
        <table class="tabla" style="width: 100%;">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 20%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 20%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 20%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 20%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 20%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</body>

</html>