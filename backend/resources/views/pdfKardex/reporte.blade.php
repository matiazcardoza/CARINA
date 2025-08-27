

<html>
<head>
<script type="text/php">
if (isset($pdf)) {
    // Este script corre en CADA página, pero pintamos solo en la última.
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        if ($pageNumber !== $pageCount) {
            return; // no dibujar en páginas intermedias
        }

        // === Medidas de la página y márgenes (SINCRONIZAR con tu CSS @page) ===
        $w = $canvas->get_width();   // ancho total de la página
        $h = $canvas->get_height();  // alto total de la página

        $marginLeft   = 50;          // igual que @page left
        $marginRight  = 50;          // igual que @page right
        $marginBottom = 40 + 120;    // 40 extra + var(--sign-h)=120  -> igual que @page bottom calc(40px + var(--sign-h))

        // === Geometría del bloque de firmas ===
        $gap  = 10;                  // separación horizontal entre cajas
        $boxH = 120;                 // altura de cada caja (== var(--sign-h) si quieres que ocupen todo)
        $usableW = $w - ($marginLeft + $marginRight);
        $boxW = ($usableW - 3 * $gap) / 4;

        // Y = borde superior del bloque de firmas (pegado al fondo del área útil)
        // coordenadas dompdf: (0,0) arriba-izquierda, y aumenta hacia abajo
        $y = $h - $marginBottom + 10; // un pequeño aire de 10px dentro del margen

        // Etiquetas de las 4 cajas
        $labels = ['ALMACENERO', 'ADMINISTRADOR', 'RESIDENTE DE OBRA', 'SUPERVISOR'];
        $font   = $fontMetrics->get_font('DejaVu Sans', 'normal');
        $size   = 10;

        for ($i = 0; $i < 4; $i++) {
            $x = $marginLeft + $i * ($boxW + $gap);

            // Dibujar el rectángulo con 4 líneas (más compatible que rectangle()+stroke() según versión)
            $canvas->line($x,          $y,          $x + $boxW, $y);
            $canvas->line($x + $boxW,  $y,          $x + $boxW, $y + $boxH);
            $canvas->line($x,          $y + $boxH,  $x + $boxW, $y + $boxH);
            $canvas->line($x,          $y,          $x,         $y + $boxH);

            // Texto centrado en la parte baja de la caja
            $text = $labels[$i];
            $textW = $fontMetrics->getTextWidth($text, $font, $size);
            $textX = $x + ($boxW - $textW) / 2;
            $textY = $y + $boxH - 12;    // 12px de margen inferior dentro de la caja
            $canvas->text($textX, $textY, $text, $font, $size);
        }
    });
}
</script>
    <title>Lista de items</title>
    <style>
        :root { 
            --header-h: 230px; 
            --sign-h:   120px;   /* altura total ocupada por las firmas */
        }  /* <- AJUSTA a la altura real del encabezado */

        @page {

            /* margin: 50px 50px; 
            margin: var(--header-h) 50px 40px 50px; */
            font-family: DejaVu Sans, sans-serif;
            margin: var(--header-h) 50px calc(40px + var(--sign-h)) 50px;
        }

         .table_01 thead { 
            display: table-header-group; 
            break-inside: avoid-page;      /* estándar */
        } 
         .table_01 tr { page-break-inside: avoid; }
        /* Header fijo impreso en cada página, colocado dentro del margen superior */
            #doc-header {
            margin-top: 25px;
            /* border: 1px solid red; */
            position: fixed;
            top: calc(-1 * var(--header-h));
            left: 0; right: 0;
            height: var(--header-h);
            }
        /* ===== UNIFICAR BORDES: color y grosor ===== */
                                            /* Color y grosor únicos */
                                            :root { --bcolor: #3f3f3fff; --bsize: 1px; }  /* ajusta 1px si los quieres más gruesos */

                                            /* Evita dobles bordes */
                                            table { border-collapse: collapse !important; }

                                            /* Tablas principales */
                                            .box th, .box td,
                                            .table_01 th, .table_01 td,
                                            .totales th, .totales td,
                                            .firmas td,
                                            .signature_container,
                                            .signature_item {
                                            border: var(--bsize) solid var(--bcolor) !important;
                                            }

                                            /* Quita reglas viejas que cambiaban color/grosor en la caja izquierda */
                                            .caja-izq {
                                            border: var(--bsize) solid var(--bcolor) !important;
                                            }

                                            /* Si no quieres líneas grises en la tabla de datos */
                                            .th_table_01, .td_table_01 { border-color: var(--bcolor) !important; }

                                            /* (opcional) encabezados sin gris de fondo */
                                            .th_table_01 { background-color: #f2f2f2; } /* o quítalo si lo quieres blanco */

        /* ===== Estilos generales ===== */
        /* * {
            margin: 0px;                
            padding: 0px;               
            box-sizing: border-box;
        }
        html{
            padding: 1rem;
            margin: 2rem;
            margin-bottom: 0;
        } */

        body {
            /* font-family: DejaVu Sans, sans-serif; */
            font-size: 12px;
            font-family: "DejaVu Sans", sans-serif;
        }


/* ===== Estilos para tabla de encabezados uno ===== */
        table.box { width: 100%; border-collapse: separate; margin-bottom: 4px; }
        table.box td, table.box th { border: 1px solid #000; padding: 6px; }

        /* Bloque superior */
        .left-panel   { width: 40%; height: 10px; text-align: center; vertical-align: middle; background: #e4e4e4ff; font-size: 2rem; }
        .obra         { height: 25px; vertical-align: middle; }
        .obra-empty   { height: 25px; }

        /* Bloque inferior */
        .material     { width: 70%; height: 40px; vertical-align: middle; padding-left: 12px; }
        .subhead      { width: 25%; text-align: center; }
        .subcell      { height: 25px; } /* área en blanco bajo los encabezados */
/* ===== Estilos para detalles ===== */
        .details_container{
            width: 100%;
        }

            .details_row{
                margin-bottom: 0.5rem;
            }

                .details_title{
                    /* color: red; */
                    /* font-weight: 600; */
                    font-size: 14px;
                }
                .details_item{
                    color: #2c3e50;
                }

/* ===== Estilos de la tabla ===== */
        .table_01 {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .th_table_01, .td_table_01 {
            border: 1px solid #bbb;
            padding: 8px;
            text-align: left;
        }

            .th_table_01 {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            /* Texto alineado al centro para el número */
            .td_table_01:first-child {
                text-align: center;
                font-weight: bold;
            }
        /* Fila alternada */
        .tbody_table_01, tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .signature_container{
            page-break-inside: avoid; 
            margin-top: 1rem;
            
            /* border: 1px solid red; */
            font-size: 0;
            height: 120px;
            /* display: inline-block; */
            /* width: 100%; */
            border: 0.5px solid #bbb;
        }
            .signature_item{
                /* dis */
                height: 100%;
                font-size: 12px;
                vertical-align: top; 
                box-sizing: border-box;
                border: 0.5px solid #bbb;
                display: inline-block;
                width: 33.19%;
                padding: 0px;
                margin: 0px;

                position: relative;
            }
                .signature_title{
                    /* color: gray; */
                    /* border: 1px solid orange; */
                    
                    font-size: 12px;
                    font-weight: 400;
                    position: absolute;
                    bottom: 10px;
                    width: 100%;
                    text-align: center;
                }

        .observations_styles{
            margin-top: 1rem;
            /* margin-top: 0.3rem; */
            /* font-size: 14px; */
            /* border-top: 1px solid gray; */
            /* background: yellow; */
            /* border-bottom: 1px solid gray; */
        }
            .observations_title{
                font-size: 12px;
                /* background: orange; */
                margin-bottom: 0.5rem;
                
            }
            .observations_container{
                /* background: pink; */
                font-size: 12px;
                font-weight: 400;
                /* border-bottom: 1px solid gray;} */
                border-top: 1px solid gray;
                padding-bottom: 0.5rem;
                padding-top: 0.5rem;
                margin-bottom: 0.5rem;
            }
        
            .container_qr_code{
                /* border: 2px solid red; */
                width: 100%;
            }
                .qr_code{
                    display: inline-block;
                    /* border: 2px solid blue; */
                    vertical-align: top;
                    margin-left: 1.4rem;

                }
/* ===== Estilos para detalles ===== */
/* ===== Estilos para detalles ===== */
        .stock_container{
            margin-top: 1rem;
            /* background: #FFADDD; */
            /* display: inline-block; */
            width: 100%;
            /* display: block; */
            display: inline-block;
                /* vertical-align: top; */
            vertical-align: top;
            
        }

            .stock_row{
                /* display: inline-block;
                vertical-align: top; */
                    /* margin-left: 1.4rem; */
                /* background: #EDFFAD; */
                /* display: inline-block; */
                /* vertical-align: top; */
                /* width: 100%; */
                display: inline-block;
                /* vertical-align: top; */
                vertical-align: top;
                margin-bottom: 0.5rem;
                margin-right: 0.5rem;
            }

                .stock_title{
                    /* color: red; */
                    /* font-weight: 600; */
                    color: black;
                    font-size: 14px;
                }
                .stock_item{
                    color: gray;
                    /* background: pink; */
                    color: #2c3e50;
                    text-align: end;
                    text-align: right;
                    width: 100%;
                }
/* ===== Estilos PARA FIRMAS ===== */
    table.firmas { width:100%; border-collapse:collapse; table-layout:fixed; margin-top:10px; }
    table.firmas td { border:1px solid #000; padding:0; height:120px; page-break-inside:avoid; }

    /* Para ubicar el texto cerca del borde inferior */
    .sigbox   { position:relative; height:120px; }
    .siglabel { position:absolute; left:0; right:0; bottom:10px; text-align:center; font-weight: bold; }

  /* ===== Estilos PARA DETALLES STOCK, TOTALES ENTRADAS Y SALIDAS  ===== */
 table.totales { width:100%; border-collapse:collapse; table-layout:fixed; margin-top:8px; }
  table.totales th, table.totales td { border:1px solid #000; padding:4px; text-align:center; }


  /* Caja grande a la izquierda */
  .caja-izq{
    width:70%;
    height: 2px;              /* ajusta la altura a tu gusto */
    text-align:left;
    vertical-align:top;
    /* Bordes rojos (gana por ser más grueso) */
    border-left:1px solid rgba(255, 255, 255, 1) !important;
    border-top:1px solid rgba(255, 255, 255, 1) !important;
    border-bottom:1px solid rgba(255, 255, 255, 1) !important;
    /* El borde derecho queda negro para separar del bloque de totales */
    border-right:10.5x solid #000 !important;
    /* background-color: red; */
  }

  /* Encabezados y celdas de totales */
  .th-titulo { font-weight:bold; }      /* usa 700 si quieres más grueso */
  .td-valor  { height:15px; border: 1px solid black; }          /* altura de la fila de valores */
  /* Permite cortes de página sanos en tablas y evita partir filas */
.table_01 { page-break-inside: auto; }
.table_01 tr { page-break-inside: avoid; page-break-after: auto; }
.table_01 thead { display: table-header-group; }  /* opcional: para repetir cabecera de la tabla de movimientos */


</style>
</head>
<body>

<!-- BLOQUE SUPERIOR -->
<header id="doc-header">
    <table class="box">
    <tr>
        <td class="left-panel" rowspan="2">CONTROL DE MATERIALES</td>
        <td class="obra">OBRA: "{{ $pdf_details['product']['desmeta'] ?? '—' }}"</td>
        
    </tr>
    <tr>
        <td class="obra-empty">&nbsp;</td>
    </tr>
    </table>

    <!-- BLOQUE INFERIOR -->
    <table class="box">
    <tr>
        <td class="material" rowspan="2">MATERIAL001: {{$pdf_details["product"]["item"]}}</td>
        <th class="subhead">UNIDAD</th>
        <th class="subhead">CODIGO</th>
    </tr>
    <tr>
        <td class="subcell">&nbsp;</td>
        <td class="subcell">&nbsp;</td>
    </tr>
    </table>
</header>

<!-- <div class="title_container">
    <h1 class="principal_title">Control visible de almacen</h1>
    <h1 class="secondary_title">Artículo: {{$pdf_details["product"]["name"]}}</h1>
</div>

<div class="details_container">
    <div class="details_row">
        <div class="details_title">Obra:</div>
        <div class="details_item">-----------</div>
    </div>
    <div class="details_row">
        <div class="details_title">Artículo:</div>
        <div class="details_item">{{$pdf_details["product"]["name"]}}</div>
    </div>
    <div class="details_row">
        <div class="details_title">Código:</div>
        <div class="details_item">{{$pdf_details["product"]["heritage_code"]}}</div>
    </div>
    <div class="details_row">
        <div class="details_title">Unidad:</div>
        <div class="details_item">-----</div>
    </div>
</div> -->




<table class="table_01">
    <thead>

        <tr>
            <th class="th_table_01" rowspan="2">#</th>
            <th class="th_table_01" rowspan="2">Fecha</th>
            <th class="th_table_01" colspan="2">Comprobante</th>
            <!-- <th class="th_table_01">Numero</th> -->
            <th class="th_table_01" rowspan="2">Tipo de movimiento</th>
            <th class="th_table_01" rowspan="2">Monto</th>
            <th class="th_table_01" rowspan="2">Nombre y Apellido (Recibido/Encargado)</th>
            <th class="th_table_01" rowspan="2">Observaciones</th>
        </tr>
        <tr>
            <!-- <th class="th_table_01">#</th> -->
            <!-- <th class="th_table_01">Fecha</th> -->
            <th class="th_table_01">Clase</th>
            <th class="th_table_01">Numero</th>
            <!-- <th class="th_table_01">Tipo de movimiento</th> -->
            <!-- <th class="th_table_01">Monto</th> -->
            <!-- <th class="th_table_01" >Nombre y Apellido (Recibido/Encargado)</th> -->
             <!-- <th class="th_table_01" rowspan="2">Observaciones</th> -->
        </tr>
    </thead>

    <tbody class="tbody_table_01">
        @foreach($pdf_details["movements"] as $index => $item)
            <tr>
                <!-- <td>{{ $index + 1 }}</td> -->
                <td class="td_table_01">{{ $item['id'] }}</td>
                <td class="td_table_01">{{ $item['movement_date'] }}</td>
                <!-- <td class="td_table_01">{{ $item['class'] }}</td> -->
                <td class="td_table_01">O/C</td>
                <!-- <td class="td_table_01">{{ $item['number'] }}</td> -->
                <td class="td_table_01">{{ $pdf_details["product"]["numero"]  }}</td>
                <td class="td_table_01">{{ $item['movement_type'] }}</td>
                <td class="td_table_01">{{ $item['amount'] }}</td>
                <!-- <td class="td_table_01">Julia Mamani Yampasi</td> -->
                

                <td class="td_table_01">
                    @php
                        $p = $item->people->first(); // primera persona adjunta
                        $pName = $p?->full_name ?? trim(($p->names ?? '').' '.($p->first_lastname ?? '').' '.($p->second_lastname ?? ''));
                        $pName = trim($pName);
                    @endphp
                    {{ $pName !== '' ? $pName : 'Julia Mamani Yampasi' }}
                </td>

                <td class="td_table_01">Ninguna</td>
            </tr>
        @endforeach
    </tbody>

</table>




<table class="totales">
  <tr>
    <td class="caja-izq" rowspan="2">
      &nbsp;  <!-- deja espacio en blanco; pon texto si quieres -->
    </td>
    <th class="th-titulo">Total entrada</th>
    <th class="th-titulo">Total Salida</th>
    <th class="th-titulo">Stock</th>
  </tr>
  <tr>
    <td class="td-valor">{{ $pdf_details['totalEntradas'] }}</td>
    <td class="td-valor">{{ $pdf_details['totalSalidas']  }}</td>
    <td class="td-valor">{{ $pdf_details['stockFinal']  }}</td>
  </tr>
</table>


<table class="firmas">
  <tr>
    <td>
      <div class="sigbox">
        <div class="siglabel">ALMACENERO</div>
      </div>
    </td>
    <td>
      <div class="sigbox">
        <div class="siglabel">ADMINISTRADOR</div>
      </div>
    </td>
    <td>
      <div class="sigbox">
        <div class="siglabel">RESIDENTE DE OBRA</div>
      </div>
    </td>
    <td>
      <div class="sigbox">
        <div class="siglabel">SUPERVISOR</div>
      </div>
    </td>
  </tr>
</table>

<!-- <table class="lastpage" style="page-break-before: always; width:100%; border-collapse: collapse; height:100%;">
  <tr>

    <td style="height:100%; border: none;">&nbsp;</td>
  </tr>
  <tr>
    <td style="border: none;">


      <table class="firmas" style="width:100%; border-collapse:collapse; table-layout:fixed;">
        <tr>
          <td><div class="sigbox"><div class="siglabel">ALMACENERO</div></div></td>
          <td><div class="sigbox"><div class="siglabel">ADMINISTRADOR</div></div></td>
          <td><div class="sigbox"><div class="siglabel">RESIDENTE DE OBRA</div></div></td>
          <td><div class="sigbox"><div class="siglabel">SUPERVISOR</div></div></td>
        </tr>
      </table>

    </td>
  </tr>
</table> -->

</body>
<html>
