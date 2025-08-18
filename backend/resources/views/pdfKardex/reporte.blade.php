

<html>
<head>
    <meta charset="UTF-8">
    <title>Lista de Personas</title>
    <style>
        /* ===== Estilos generales ===== */
        * {
            margin: 0px;                /* ✅ Sí funciona */
            padding: 0px;               /* ✅ Sí funciona */
            box-sizing: border-box;
        }
        html{
            background: orange;
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
            background: yellow;
            /* border-bottom: 1px solid gray; */
        }
            .observations_title{
                font-size: 12px;
                background: orange;
                margin-bottom: 0.5rem;
                
            }
            .observations_container{
                background: pink;
                font-size: 12px;
                font-weight: 400;
                border-bottom: 1px solid red;
                border-top: 1px solid red;
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
    </style>
</head>
<body>
<div>
    <h1>Control visible de almacen</h1>
    <h1>Artículo: Cemento</h1>
</div>

<table>

    <thead>
        <meta charset="UTF-8">
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
        </tr>
    </thead>
    <tbody>
        @foreach($personas as $index => $persona)
            <tr>
                <!-- <td>{{ $index + 1 }}</td> -->
                <td>{{ $persona['id'] }}</td>
                <td>{{ $persona['nombre'] }}</td>
                <td>{{ $persona['email'] }}</td>
                <td>{{ $persona['telefono'] }}</td>
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

<div class="observations_styles">
    <h2 class="observations_title">Observaciones</h2>
    @foreach($personas as $index => $persona)
        <h2 class="observations_container">{{ $persona['id'] }}: {{ $persona['observations'] }}
            <div class="container_qr_code">
            @if(!empty($persona['codigos_qr']))
                <!-- <div class="qr_code">XXXXXXX</div> -->
                @foreach($persona['codigos_qr'] as $qr)
                    <!-- <div class="qr_code">{{ $qr }}</div> -->
                    <div class="qr_code">HHHHHH</div>
                @endforeach
            @endif
            </div>
        </h2>
    @endforeach
</div>

</body>
<html>

        @if(!empty($persona['observations']))
            <h2 class="observations_container">{{ $persona['id'] }}: {{ $persona['observations'] }}</h2>
        @else
            <h2 class="observations_container">{{ $persona['id'] }}: Sin observaciones registradas.</h2>
        @endif