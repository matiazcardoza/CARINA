

<html>
<head>
    <meta charset="UTF-8">
    <title>Lista de items</title>
    <style>
        /* ===== Estilos generales ===== */
        * {
            margin: 0px;                /* ✅ Sí funciona */
            padding: 0px;               /* ✅ Sí funciona */
            box-sizing: border-box;
        }
        html{
            /* background: orange; */
            padding: 1rem;
            margin: 2rem;
            margin-bottom: 0;
        }
        body {

            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            /* color: #333; */
            /* margin: 20px; */
            /* padding: 2rem; */
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .title_container{
            /* background: pink; */
            margin-bottom: 8px;
        }
        .title_container > h1 {
            margin-bottom: 0px;
        }
            .secondary_title{
                font-size: 1.2rem;
            }

        /* ===== Estilos para detalles ===== */
        .details_container{
            /* background: #FFADDD; */
            /* display: inline-block; */
            width: 100%;
            /* display: block; */
            /* display: inline; */
                /* vertical-align: top; */
            /* vertical-align: top; */
        }

            .details_row{
                /* display: inline-block;
                vertical-align: top; */
                    /* margin-left: 1.4rem; */
                /* background: #EDFFAD; */
                /* display: inline-block; */
                /* vertical-align: top; */
                /* width: 100%; */
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #bbb;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        /* Fila alternada */
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Texto alineado al centro para el número */
        td:first-child {
            text-align: center;
            font-weight: bold;
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
    </style>
</head>
<body>

<div class="title_container">
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
</div>

<table>

    <thead>
        <meta charset="UTF-8">
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Clase</th>
            <th>Numero</th>
            <th>Tipo de movimiento</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($pdf_details["movements"] as $index => $item)
            <tr>
                <!-- <td>{{ $index + 1 }}</td> -->
                <td>{{ $item['id'] }}</td>
                <td>{{ $item['movement_date'] }}</td>
                <td>{{ $item['class'] }}</td>
                <td>{{ $item['number'] }}</td>
                <td>{{ $item['movement_type'] }}</td>
                <td>{{ $item['amount'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>


<div class="signature_container">

        <div class="signature_item">
            <h2 class="signature_title">Ingeniero supervisor</h2>
        </div>

        <div class="signature_item">
            <h2 class="signature_title">Asistente administrativo</h2>
        </div>

        <div class="signature_item">
            <h2 class="signature_title">Almacenero de obras</h2>
        </div>
</div>

<div class="stock_container">
    <div class="stock_row">
        <div class="stock_title">Total entrada:</div>
        <div class="stock_item">{{$pdf_details["totalEntradas"]}}</div>
    </div>
    <div class="stock_row">
        <div class="stock_title">Total salida:</div>
        <div class="stock_item">{{$pdf_details["totalSalidas"]}}</div>
    </div>
    <div class="stock_row">
        <div class="stock_title">Stock:</div>
        <div class="stock_item">{{$pdf_details["stockFinal"]}}</div>
    </div>
</div>

<div class="observations_styles">
    <h2 class="observations_title">Observaciones</h2>
    @foreach($pdf_details["movements"] as $index => $item)

        @if(!empty($item['observations']))        
            <h2 class="observations_container">{{ $item['id'] }}: {{ $item['observations'] }}
                <div class="container_qr_code">
                @if(!empty($item['qr_codes']))
                    <!-- <div class="qr_code">XXXXXXX</div> -->
                    @foreach($item['qr_codes'] as $qr)
                        <!-- <div class="qr_code">{{ $qr }}</div> -->
                        <div class="qr_code">HHHHHH</div>
                    @endforeach
                @endif
                </div>
            </h2>
        @endif
    @endforeach
</div>

</body>
<html>

        @if(!empty($item['observations']))
            <h2 class="observations_container">{{ $item['id'] }}: {{ $item['observations'] }}</h2>
        @else
            <h2 class="observations_container">{{ $item['id'] }}: Sin observaciones registradas.</h2>
        @endif