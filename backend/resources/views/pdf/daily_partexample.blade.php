<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Anexo 01</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm 0.5cm 4.5cm 0.5cm;
        }

        html {
            width: 100%;
        }

        body {
            margin: 10px;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: <?= isset($pdf) && $pdf == true ? '0.6rem' : '0.6rem' ?>;
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
            text-transform: uppercase;
            font-size: <?= isset($pdf) && $pdf == true ? '1rem' : '1rem' ?>;
        }

        .h2 {
            text-align: center;
            color: #333;
            text-transform: uppercase;
            font-size: <?= isset($pdf) && $pdf == true ? '0.8rem' : '0.8rem' ?>;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #ddd;
            padding: <?= isset($pdf) && $pdf == true ? '3px' : '3px' ?>;
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
                    <td style="width: 70%; border-right: 1px solid gray; text-align: justify; vertical-align: bottom; height: 100px;">
                        <div class="pagenum" style="font-size: 12px;"></div>
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

    <table class="tabla">
        <thead>
            <tr>
                <th colspan="9" style="border: none; background: none; text-align: center;">
                    <span class="h1">ANEXO 01</span>
                </th>
            </tr>
            <tr>
                <th colspan="9" style="border: none; background: none; text-align: center;">
                    <span class="h1">PLANILLA PRE-ELABORADA {{ $mes }} del {{ $anio }}</span>
                </th>
            </tr>
            <tr>
                <th colspan="9" style="border: none; background: none; text-align: center;">
                    <span class="h2">{{ $proyecto }}</span>
                </th>
            </tr>
            <tr>
                <th colspan="9" style="border: none; background: none; text-align: center;">
                    &nbsp;
                </th>
            </tr>

            <tr>
                <th style="text-align: center;">N°</th>
                <th colspan="3" style="text-align: center;">Nombres y Apellidos</th>
                <th style="text-align: center;">Cargo</th>
                <th style="text-align: center;">Remuneración Mensual</th>
                <th style="text-align: center;">Fecha de Ingreso</th>
                <th style="text-align: center;">Fecha de Nacimiento</th>
                <th style="text-align: center;">N° L.E. O DNI</th>
            </tr>
        </thead>

        <tbody>
            @foreach($personal as $orden => $item)
            <tr>
                <td style="text-align: center; width: 10px;">{{ $orden+1 }}</td>
                <td style="text-align: left;">{{$item->nombres}}</td>
                <td style="text-align: left;">{{$item->ap_paterno}}</td>
                <td style="text-align: left;">{{$item->ap_materno}}</td>
                <td style="text-align: left;">{{$item->asistencia_cargo_actual->cargo}}</td>
                <td>{{number_format($item->tot_ing, 2)}}</td>
                <td style="text-align: center;">{{formato_fecha_es($item->fec_ini)}}</td>
                <td style="text-align: center;">{{formato_fecha_es($item->fec_nacimiento)}}</td>
                <td style="text-align: center;">{{$item->num_doc}}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($pdf == true)
    <div style="position: absolute; bottom: -2.30cm;; width: 100%;">
        <table class="tabla" style="width: 100%;">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 25%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 25%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 25%;">
                        <br><br><br><br><br><br><br><br><br>
                        Firma o VB
                    </td>
                    <td style="text-align: center; width: 25%;">
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