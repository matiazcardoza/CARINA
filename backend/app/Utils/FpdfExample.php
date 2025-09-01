<?php
namespace App\Utils;



class FpdfExample extends \FPDF {

    protected string $headerLogoPath = '';
    function Header() {


        // Encabezado opcional
        $x = $this->lMargin;              // margen izquierdo
        $y = 5;                         // posición vertical
        $w = $this->GetPageWidth() - $this->lMargin - $this->rMargin; // ancho útil
        $h = 25;

        
        // Imagen
        $rutaImagen = 'img/gr-puno-escudo.png';
        $ancho_image = 14;
        $this->Image($rutaImagen, $x, $y, $ancho_image);

        // Imagen del codigo rq
        $rutaImagenQr = $this->headerLogoPath;
        $ancho_image = 14;
        $this->Image($rutaImagenQr, $x + 173, $y+0.5, $ancho_image + 3);
        

        // Insertar titulos
        $y_vertical_titles = 3 + $y;
        $padding_izquierdo_titles = 4;
        $this->SetXY($x + $ancho_image + $padding_izquierdo_titles , $y_vertical_titles);
        $ancho_titulos = 75;
        $alto_titulos = 4;
        $this->SetFont('Arial','',12);
        $this->Cell($ancho_titulos,$alto_titulos, 'Gobierno Regionarl de Puno',0,1,'L',false);
        $this->SetFont('Arial','',8);
        $this->SetXY($x + $ancho_image + $padding_izquierdo_titles , $y_vertical_titles + $alto_titulos);
        $this->Cell($ancho_titulos,$alto_titulos, 'Movimiento de Almacen',0,1,'L',false);
        $this->SetFont('Arial','',8);
        $this->SetXY($x + $ancho_image + $padding_izquierdo_titles , $y_vertical_titles + $alto_titulos + $alto_titulos);
        $this->Cell($ancho_titulos,$alto_titulos, 'RUC: 20406325815',0,1,'L',false);
        // $this->SetX($ancho_titulos + $x + $ancho_image);
        // $this->SetY($y);
        $this->SetXY($ancho_titulos + $x + $ancho_image +21,  $y+1);

        // Insertar texto QR
        $texto = "Esta es una representación impresa cuya autenticidad puede ser contrastada con la representación imprimible localizada en la sede digital del Gobierno Regional Puno, aplicando lo dispuesto por el Art. 25 de D.S. 070-2013-PCM y la Tercera Disposición Complementaria Final del D.S. 026-2016-PCM. Su autenticidad e integridad pueden ser contrastadas a través de este QR:";
        // $this->SetXY($x + $ancho_image, $y);
        $this->SetFont('Arial','',6);
        $this->MultiCell(
            60,       // ancho en mm
            2.5,         // alto de línea
            $this->enc((string)$texto),    // texto
            0,         // borde (0 = no, 1 = sí)
            'J',       // alineación: L, C, R, J (justificado)
            false       // relleno: false = no, true = sí
        );



        $this->SetDrawColor(255, 255, 255);  
        $this->Rect($x, $y, $w, $h);
        // posicionamos el cursor al final del rectangulo (esquina inferior derecha)
        $this->SetXY($x, $y + $h);



        // $this->SetXY($x, $y);
        // $this->SetFont('Arial', 'B', 12);
        // $this->Cell(0, 50, 'Mi primer PDF con FPDF', 1, 1, 'C');

    }

    public function setHeaderLogo(?string $path): self
    {
        $this->headerLogoPath = $path;
        return $this;
    }


    // function Footer() {
    //     // Pie de página con número de página
    //     $this->SetY(-15);
    //     $this->SetFont('Arial', 'I', 8);
    //     $this->AliasNbPages(); // Activa el alias {nb}
    //         // $this->Cell(0, 30, 'hola mundo', 1, 0, 'C', true); // ← Activar fondo con `true`
    //     $this->Cell(0, 10, 'Página '.$this->PageNo().' de {nb}', 1, 0, 'C', true);
    // }

    function MyBody(){
        $this->AddPage();
        $this->SetFillColor(120, 220, 220);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0,30,'hola mundo',1,1,'C', true);
    }

    function insertFinalBox($boxHeight = 30) {
        $pageHeight = $this->GetPageHeight(); // Altura total de la página
        $currentY   = $this->GetY();          // Posición vertical actual del cursor

        // Verificar si hay espacio suficiente
        if (($currentY + $boxHeight + 10) <= $pageHeight) {
            // Hay espacio: dibujar recuadro en la misma página
            // $this->SetY($pageHeight - $boxHeight - 10); // 10mm de margen inferior
            
            // $this->SetFillColor(222, 64, 31);
            // $this->SetDrawColor(0, 0, 0);
            // $this->Rect(10, $this->GetY(), $this->GetPageWidth() - 20, $boxHeight, 'DF');
            // $this->SetXY(10, $this->GetY() + 10);
            // $this->Cell(0, 10, 'Recuadro final en última página', 0, 0, 'C');
        } else {
            // No hay espacio: agregar nueva página
            // $this->AddPage();
            // $this->SetY($pageHeight - $boxHeight - 10);
            // $this->SetFillColor(222, 64, 31);
            // $this->SetDrawColor(0, 0, 0);
            // $this->Rect(10, $this->GetY(), $this->GetPageWidth() - 20, $boxHeight, 'DF');
            // $this->SetXY(10, $this->GetY() + 10);
            // $this->Cell(0, 10, 'Recuadro final en nueva página', 0, 0, 'C');
        }
    }

    function SizeTest(){
        // $pageHeight = $this->GetPageHeight();
        // $currentY   = $this->GetY();
        $this->AddPage();
        $this->SetFont('Arial', 'I', 8);
        // $this->Cell(0,40,'prueba de valores por defecto de fpdf. altura total:'.$pageHeight ,1 ,1 ,'C' ,false);
        $this->Cell(0,40,'prueba de valores por defecto de fpdf. valor actual del cursor:'.$this->GetY(),1 ,0 ,'C' ,false);
        $this->Cell(0,40,'cursor:'.$this->GetY(),1 ,1 ,'C' ,false);
    }

    function SignatureBoxTest(){

        if ($this->PageNo() === 0) {
            $this->AddPage();
        }

        $availableSpace = 0;
        $this->SetAutoPageBreak(true, 10);
        // $this->AddPage();
        $this->SetFont('Arial', 'I', 8);
        // for($i=0; $i<=10; $i++){
        //     $this->Cell(0,46,'test'.$this->GetY(),1 ,1 ,'C' ,false);
        // };

        // calculamos el espacio disponible
        $availableSpace = $this->GetPageHeight() - ($this->GetY() + 10)  ;

        if(35 <= $availableSpace){
            // $this->SetDrawColor(255, 0, 0);
            $this->SetY($this->GetPageHeight()-(10+35));	
            $this->drawSignBoxesSimple();
            // $this->Cell(0,46,'espacio reservado',1 ,1 ,'C' ,false);
        }else{
            
            $this->AddPage();
            // $this->SetDrawColor(255, 0, 0);
            $this->SetY($this->GetPageHeight()-(10+35));	
            $this->drawSignBoxesSimple();
            // $this->Cell(0,46,'espacio reservado',1 ,1 ,'C' ,false);
        }

    }

    /**
     * Dibuja una tabla con celdas que crecen verticalmente (MultiCell) y anchos controlables.
     *
     * @param array $headers  p.ej. ['ITEM','DESCRIPCIÓN','OBSERVACIONES']
     * @param array $rows     p.ej. [['1','Texto','OK'], ...]
     * @param array|null $widths  mm directos [35,60,40] | fracciones [0.2,0.5,0.3] | porcentajes [20,50,35]
     * @param array $opt      lineHeight,padX,padY,aligns,border,headerFill,fontHeader,fontBody
     */
    function renderTable(array $headers, array $rows, ?array $widths = null, array $opt = [])
    {
        // Asegura que exista página
        if ($this->PageNo() === 0) {
            $this->AddPage();
        }

        // --- Opciones ---
        $lineH      = $opt['lineHeight'] ?? 6;
        $padX       = $opt['padX'] ?? 2;
        $padY       = $opt['padY'] ?? 1;
        $border     = $opt['border'] ?? 1; // 1 con bordes, 0 sin bordes
        $headerFill = $opt['headerFill'] ?? [240,240,240];
        $fontHeader = $opt['fontHeader'] ?? ['Arial','B',10];
        $fontBody   = $opt['fontBody'] ?? ['Arial','',9];

        // --- Columnas / Anchos ---
        $usableW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // si no hay $widths, infiere por #headers
        if ($widths === null) {
            $colCount = max(1, count($headers));
            $widths = array_fill(0, $colCount, $usableW / $colCount);
        }
        $colCount = count($widths);

        // aligns por columna
        $aligns = $opt['aligns'] ?? array_fill(0, $colCount, 'L');

        // normaliza headers al # de columnas
        $headers = array_values($headers);
        if (count($headers) < $colCount) {
            $headers = array_pad($headers, $colCount, '');
        } else {
            $headers = array_slice($headers, 0, $colCount);
        }

        // interpreta anchos: fracciones (0..1) o porcentajes (~100) o mm
        $allFractions = count(array_filter($widths, fn($w)=>$w>0 && $w<=1)) === $colCount;
        $allPercents  = (count(array_filter($widths, fn($w)=>$w>=0 && $w<=100)) === $colCount)
                        && abs(array_sum($widths) - 100) < 0.01;

        if ($allFractions) {
            $widths = array_map(fn($f)=>$usableW * $f, $widths);
        } elseif ($allPercents) {
            $widths = array_map(fn($p)=>$usableW * $p / 100, $widths);
        } // si no, se asume mm

        $pageBreakTrigger = $this->GetPageHeight() - $this->bMargin;

        // --- Contador de líneas (medición con texto convertido) ---
        // --- Contador de líneas (medición con texto convertido) ---
        $countLines = function(string $text, float $cellW) use ($padX) {
            $text = $this->enc($text);
            $cm   = $this->cMargin;   // <-- NUEVO: margen interno de MultiCell
            $eps  = 0.1;              // <-- NUEVO: margen por redondeos en mm

            // El ancho efectivo que realmente usa MultiCell:
            $maxW = max(0.1, $cellW - 2*$padX - 2*$cm);  // <-- CAMBIO CLAVE

            $segs  = preg_split("/\r?\n/", $text);
            $lines = 0;
            foreach ($segs as $seg) {
                $seg = trim($seg);
                if ($seg === '') { $lines++; continue; }
                $lineW = 0;
                foreach (preg_split('/\s+/', $seg) as $i => $word) {
                    $token = ($i>0 ? ' ' : '').$word;
                    $wTok  = $this->GetStringWidth($token);
                    if ($lineW + $wTok <= $maxW + $eps) {   // <-- usa eps
                        $lineW += $wTok;
                    } else {
                        $lines++;
                        $lineW = $this->GetStringWidth($word);
                    }
                }
                $lines++;
            }
            return max(1, $lines);
        };


        // --- Dibuja una fila completa ---
        $drawRow = function(array $cells) use ($widths, $aligns, $lineH, $padX, $padY, $border, &$pageBreakTrigger, $countLines) {
            // Normaliza celdas al # de columnas (vacías no rompen)
            $cells = array_values($cells);
            $cells = array_slice($cells, 0, count($widths));
            if (count($cells) < count($widths)) {
                $cells = array_pad($cells, count($widths), '');
            }

            $x0 = $this->GetX(); $y0 = $this->GetY();

            // altura de fila: máximo de líneas entre celdas
            $maxLines = 1;
            foreach ($cells as $i=>$txt) {
                $maxLines = max($maxLines, $countLines((string)$txt, $widths[$i]));
            }
            $rowH = $maxLines * $lineH + 2*$padY;

            // salto de página si no cabe
            if ($y0 + $rowH > $pageBreakTrigger) {
                $this->AddPage();
                $pageBreakTrigger = $this->GetPageHeight() - $this->bMargin;
                $x0 = $this->GetX(); $y0 = $this->GetY();
            }

            // pinta cada celda
            foreach ($cells as $i=>$txt) {
                $w = $widths[$i];
                $x = $this->GetX(); $y = $this->GetY();

                if ($border) $this->Rect($x, $y, $w, $rowH);   // contenedor
                $this->SetXY($x + $padX, $y + $padY);
                $this->MultiCell($w - 2*$padX, $lineH, $this->enc((string)$txt), 0, $aligns[$i] ?? 'L'); // imprimir CON enc()
                $this->SetXY($x + $w, $y); // siguiente celda
            }

            // cursor a la siguiente fila
            $this->SetXY($x0, $y0 + $rowH);
        };

        // --- Header ---
        [$fhFam,$fhSty,$fhSz] = $fontHeader;
        $this->SetFont($fhFam,$fhSty,$fhSz);

        // altura del header (medición con enc)
        $maxLinesH = 1;
        foreach ($headers as $i=>$h) {
            $maxLinesH = max($maxLinesH, $countLines((string)$h, $widths[$i]));
        }
        $hHead = $maxLinesH*$lineH + 2*$padY;

        // salto si no cabe el header
        if ($this->GetY() + $hHead > $pageBreakTrigger) {
            $this->AddPage();
            $pageBreakTrigger = $this->GetPageHeight() - $this->bMargin;
        }

        // header fondo + texto
        $this->SetFillColor($headerFill[0], $headerFill[1], $headerFill[2]);
        $x = $this->GetX(); $y = $this->GetY();
        $mode = $border ? 'DF' : 'F';
        for ($i=0; $i<count($widths); $i++) {
            $w = $widths[$i];
            $xCell = $this->GetX(); $yCell = $this->GetY();
            $this->Rect($xCell, $yCell, $w, $hHead, $mode);
            $this->SetXY($xCell + $padX, $yCell + $padY);
            $this->MultiCell($w - 2*$padX, $lineH, $this->enc((string)($headers[$i] ?? '')), 0, $aligns[$i] ?? 'L');
            $this->SetXY($xCell + $w, $yCell);
        }
        $this->SetXY($x, $y + $hHead);

        // --- Body ---
        [$fbFam,$fbSty,$fbSz] = $fontBody;
        $this->SetFont($fbFam,$fbSty,$fbSz);

        foreach ($rows as $row) {
            $drawRow(is_array($row) ? $row : [$row]); // soporta filas no-array por si acaso
        }
    }


    /**
     * Convierte UTF-8 a Windows-1252 preservando tildes y comillas tipográficas.
     * Incluye fallback seguro con mb_convert_encoding().
     */
    protected function enc(string $s): string
    {
        if ($s === '') {
            return '';
        }

        // Intento principal: UTF-8 → Windows-1252 con transliteración
        $out = iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);

        // Fallback si iconv() falla
        if ($out === false) {
            $out = mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        }

        return $out;
    }

    /**
     * Pinta resumen tipo Kardex:
     *  OBRA, MATERIAL, COMPROBANTE  (2 columnas: etiqueta/valor, altura auto)
     *  Totales (3 cajas horizontales: entrada, salida, stock, altura auto)
     */
    function drawKardexSummary(
        string $obra,
        string $material,
        string $comprobante,
        $totalEntrada,
        $totalSalida,
        $stock,
        array $opt = []
    ){
        if ($this->PageNo() === 0) $this->AddPage();

        // Opciones
        $lineH     = $opt['lineHeight'] ?? 6;
        $padX      = $opt['padX'] ?? 2;
        $padY      = $opt['padY'] ?? 1;
        $labelW    = $opt['labelW'] ?? 35;
        $fontLabel = $opt['fontLabel'] ?? ['Arial','B',10];
        $fontValue = $opt['fontValue'] ?? ['Arial','',10];
        $fontStat  = $opt['fontStat']  ?? ['Arial','B',10];

        $usableW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $valueW  = $usableW - $labelW;

        $pageBreakTrigger = $this->GetPageHeight() - $this->bMargin;

        // === FIX CLAVE: medir con cMargin ===
        $countLines = function(string $text, float $cellW, array $font) use ($padX, $lineH) {
            [$fam,$sty,$sz] = $font;
            $this->SetFont($fam,$sty,$sz);
            $text = $this->enc($text);

            // MultiCell usará: ancho_efectivo = cellW - 2*padX - 2*cMargin
            $cm   = $this->cMargin;
            $maxW = max(0.1, $cellW - 2*$padX - 2*$cm);
            $eps  = 0.01; // pequeño margen por redondeos

            $segs = preg_split("/\r?\n/", $text);
            $lines = 0;
            foreach ($segs as $seg) {
                $seg = trim($seg);
                if ($seg === '') { $lines++; continue; }
                $lineW = 0;
                foreach (preg_split('/\s+/', $seg) as $i => $word) {
                    $token = ($i>0?' ':'').$word;
                    $wTok  = $this->GetStringWidth($token);
                    if ($lineW + $wTok <= $maxW + $eps) {
                        $lineW += $wTok;
                    } else {
                        $lines++;
                        $lineW = $this->GetStringWidth($word);
                    }
                }
                $lines++;
            }
            return max(1,$lines);
        };

        // Alturas de filas clave-valor
        $hObra = max($countLines('OBRA',      $labelW, $fontLabel),
                    $countLines($obra,       $valueW, $fontValue)) * $lineH + 2*$padY;

        $hMat  = max($countLines('MATERIAL',  $labelW, $fontLabel),
                    $countLines($material,   $valueW, $fontValue)) * $lineH + 2*$padY;

        $hComp = max($countLines('COMPROBANTE',$labelW, $fontLabel),
                    $countLines($comprobante,$valueW, $fontValue)) * $lineH + 2*$padY;

        // Totales
        $statW = $usableW / 3;
        $txtEntrada = 'Total entrada: ' . (string)$totalEntrada;
        $txtSalida  = 'Total salida: '  . (string)$totalSalida;
        $txtStock   = 'Stock: '         . (string)$stock;

        $hStat = max(
            $countLines($txtEntrada, $statW, $fontStat),
            $countLines($txtSalida,  $statW, $fontStat),
            $countLines($txtStock,   $statW, $fontStat)
        ) * $lineH + 2*$padY;

        // Salto si no cabe todo el bloque
        $needH = $hObra + $hMat + $hComp + $hStat + 2;
        if ($this->GetY() + $needH > $pageBreakTrigger) {
            $this->AddPage();
        }

        // Helper KV
        $drawKV = function(string $label, string $value, float $rowH) use ($labelW, $valueW, $padX, $padY, $lineH, $fontLabel, $fontValue) {
            $x = $this->GetX(); $y = $this->GetY();

            // etiqueta
            $this->Rect($x, $y, $labelW, $rowH);
            [$f1,$s1,$z1] = $fontLabel; $this->SetFont($f1,$s1,$z1);
            $this->SetXY($x + $padX, $y + $padY);
            $this->MultiCell($labelW - 2*$padX, $lineH, $this->enc($label), 0, 'L');

            // valor
            $this->SetXY($x + $labelW, $y);
            $this->Rect($x + $labelW, $y, $valueW, $rowH);
            [$f2,$s2,$z2] = $fontValue; $this->SetFont($f2,$s2,$z2);
            $this->SetXY($x + $labelW + $padX, $y + $padY);
            $this->MultiCell($valueW - 2*$padX, $lineH, $this->enc($value), 0, 'L');

            $this->SetXY($x, $y + $rowH);
        };

        // Dibujo
        $drawKV('OBRA',        (string)$obra,        $hObra);
        $drawKV('MATERIAL',    (string)$material,    $hMat);
        $drawKV('COMPROBANTE', (string)$comprobante, $hComp);

        // Totales (3 cajas)
        $x = $this->GetX(); $y = $this->GetY();
        [$fsF,$fsS,$fsZ] = $fontStat; $this->SetFont($fsF,$fsS,$fsZ);

        $this->Rect($x, $y, $statW, $hStat);
        $this->SetXY($x + $padX, $y + $padY);
        $this->MultiCell($statW - 2*$padX, $lineH, $this->enc($txtEntrada), 0, 'C');

        $this->SetXY($x + $statW, $y);
        $this->Rect($x + $statW, $y, $statW, $hStat);
        $this->SetXY($x + $statW + $padX, $y + $padY);
        $this->MultiCell($statW - 2*$padX, $lineH, $this->enc($txtSalida), 0, 'C');

        $this->SetXY($x + 2*$statW, $y);
        $this->Rect($x + 2*$statW, $y, $statW, $hStat);
        $this->SetXY($x + 2*$statW + $padX, $y + $padY);
        $this->MultiCell($statW - 2*$padX, $lineH, $this->enc($txtStock), 0, 'C');

        $this->SetXY($x, $y + $hStat);
        $this->Cell(0,5,'',0,1);
    }


    function drawSignBoxesSimple()
    {
        // if ($this->PageNo() === 0) $this->AddPage();

        // Config simple
        $h = 35;          // alto de cada casilla
        $pad = 3;         // margen interno inferior
        $lineH = 6;       // alto de línea
        $labels = ['ALMACENERO','ADMINISTRADOR','RESIDENTE DE OBRA','SUPERVISOR'];

        // Cálculos básicos
        $usableW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $w = $usableW / count($labels);
        $x0 = $this->lMargin;
        $y0 = $this->GetPageHeight() - $this->bMargin - $h; // pegado abajo

        // Dibujo
        $this->SetFont('Arial','B',8);
        for ($i = 0; $i < count($labels); $i++) {
            $xi = $x0 + $i * $w;
            $this->Rect($xi, $y0, $w, $h);                                     // marco
            $this->SetXY($xi, $y0 + $h - $pad - $lineH);                       // texto abajo
            $this->Cell($w, $lineH, $this->enc($labels[$i]), 0, 0, 'C');       // centrado
        }

        // deja el cursor debajo del bloque
        $this->SetXY($x0, $y0 + $h);
    }

}

