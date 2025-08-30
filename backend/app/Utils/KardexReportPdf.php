<?php
namespace App\Utils;

class KardexReportPdf extends \FPDF
{
    /** Color de la barra superior (header) */
    private const COLOR_HEADER_BAR = [255, 255, 255]; // azul corporativo
    public const COLOR_TEXT = [33,33,33];
    public const COLOR_TABLE_BORDER = [200,205,235]; // mismo tono que la tabla
    // public float $headerBarHeight = 10.0; // default actual
    /** Título secundario opcional (no usado si el header es barra) */
    public string $headerSubtitle = '';

    /** Reserva inferior (mm) para no invadir la zona de firmas */
    protected float $reservedBottom = 0.0;

    /** ====== Header avanzado (contenido dentro de la barra) ====== */
    public float $headerBarHeight = 10.0; // alto de la barra
    public ?string $headerLogoPath = null; // ruta escudo
    public ?string $headerQRPath   = null; // ruta QR
    public array $headerCols = [0.13, 0.42, 0.06, 0.29, 0.10]; // % del ancho útil

    public string $headerTitle = 'Gobierno Regional de Puno';
    public string $headerLine1 = 'Oficina de Abastecimiento y Servicios Auxiliares';
    public string $headerLine2 = 'RUC: 123456789';
    public string $headerLegal = 'Esta es una representación impresa cuya autenticidad puede ser contrastada con la representación imprimible localizada en la sede digital del Gobierno Regional Puno, aplicando lo dispuesto por el Art. 25 de D.S. 070-2013-PCM y la Tercera Disposición Complementaria Final del D.S. 026-2016-PCM. Su autenticidad e integridad pueden ser contrastadas a través de este QR:';

    
    public static function enc(string $txt): string {
        return mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8');
    }
    
    public function SetCellMargin(float $m): void {
        $this->cMargin = $m;
    }
    // =======================
    // Meta / Márgenes
    // =======================
    public function applyMeta(array $meta): void
    {

        $this->SetTitle(self::enc($meta['title'] ?? ''));
        $this->SetAuthor($meta['author'] ?? '');

        // Config header avanzado (opcionales)
        $this->headerBarHeight = (float)($meta['header_bar_height'] ?? $this->headerBarHeight);
        $this->headerLogoPath  = $meta['header_logo_path'] ?? $this->headerLogoPath;
        $this->headerQRPath    = $meta['header_qr_path']   ?? $this->headerQRPath;
        $this->headerTitle     = $meta['header_title']     ?? $this->headerTitle;
        $this->headerLine1     = $meta['header_line1']     ?? $this->headerLine1;
        $this->headerLine2     = $meta['header_line2']     ?? $this->headerLine2;
        $this->headerLegal     = $meta['header_legal']     ?? $this->headerLegal;
        if (!empty($meta['header_cols']) && is_array($meta['header_cols'])) {
            $this->headerCols = $meta['header_cols'];
        }

        // Márgenes (top nunca menor a barra + respiro)
        $m = $meta['margins'] ?? [10, 20, 10]; // [L, T, R]
        $top = max($m[1], $this->headerBarHeight + 6.0);
        $this->SetMargins($m[0], $top, $m[2]);

        // se quiere obtener el valor del parametro "autobreak" y si este valor no llega se le asigna lo que esta al costao de los signos ??
        // se debe tomar en cuenta que si el usuario envia un valor y no un array de dos valores como se espera el programa puede lanzar un error
        $ab = $meta['autobreak'] ?? [true, self::px(40)];
        // aqui solo indicamos que espacio debe haber en la parte inferior para hacer saltos de linea
        $this->SetAutoPageBreak($ab[0], $ab[1]);
        // hace posible que se pueda usar {nb}, {nb} representa el total de paginas del pf, es decir cuando nosotros pongamos en un texto {nb} este valor sera reemplazado por el total de paginas
        $this->AliasNbPages(); 
        $this->headerSubtitle = $meta['subtitle'] ?? '';;
    }

    // =======================
    // Header / Footer
    // =======================
    public function Header(): void
    {
        $h = $this->headerBarHeight;

        // Fondo azul
        $this->SetFillColor(...self::COLOR_HEADER_BAR);
        $this->Rect(0, 0, $this->GetPageWidth(), $h, 'F');

        $pad = 4.0; // padding interno
        $x = $this->lMargin;
        $usableW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Anchos absolutos de columnas
        $colW = [];
        foreach ($this->headerCols as $p) { $colW[] = $usableW * $p; }
        $diff = $usableW - array_sum($colW);
        if (abs($diff) > 0.01) { $colW[count($colW)-1] += $diff; } // corrige redondeo

        // Separadores verticales blancos
        $this->SetDrawColor(255,255,255);
        $this->SetLineWidth(0.4);
        $xi = $x;
        for ($i=0; $i<count($colW)-1; $i++) {
            $xi += $colW[$i];
            $this->Line($xi, 0, $xi, $h);
        }

        // Texto en blanco sobre azul
        $this->SetTextColor(0,0,0);
        $this->SetDrawColor(255,255,255); // separadores blancos
        // Col 0: LOGO
        $xi = $x;
        if ($this->headerLogoPath && @is_file($this->headerLogoPath)) {
            $maxW = $colW[0] - 2*$pad;
            $maxH = $h - 2*$pad;
            // Escala por ancho (mantiene proporción)
            $this->Image($this->headerLogoPath, $xi + $pad, $pad, $maxW, 0);
        }

        // Col 1: TÍTULO y líneas
        $xi += $colW[0];
        $this->SetXY($xi + $pad, $pad + 2.0);
        $this->SetFont('Arial','B',14);
        $this->Cell($colW[1] - 2*$pad, 6, self::enc($this->headerTitle), 0, 2, 'L');
        $this->SetFont('Arial','',10);
        $this->Cell($colW[1] - 2*$pad, 4.8, self::enc($this->headerLine1), 0, 2, 'L');
        $this->Cell($colW[1] - 2*$pad, 4.8, self::enc($this->headerLine2), 0, 2, 'L');

        // Col 2: (separador visual; no contenido)

        // Col 3: TEXTO LEGAL
        $xi += $colW[1] + $colW[2];
        $this->SetXY($xi + $pad, $pad);
        $this->SetFont('Arial','',7);
        $this->MultiCell($colW[3] - 2*$pad, 3.0, self::enc($this->headerLegal), 0, 'J');

        // Col 4: QR
        $xi += $colW[3];
        if ($this->headerQRPath && @is_file($this->headerQRPath)) {
            $qrSize = min($colW[4] - 2*$pad, $h - 2*$pad);
            $this->Image(
                $this->headerQRPath,
                $xi + ($colW[4] - $qrSize)/2,
                ($h - $qrSize)/2,
                $qrSize,
                $qrSize
            );
        }
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->SetTextColor(120,120,120);
        $this->Cell(0, 10, self::enc('Página ').$this->PageNo().'/{nb}', 0, 0, 'C');
    }

    // =======================
    // Utilidades de layout
    // =======================
    public function setReservedBottom(float $h): void { $this->reservedBottom = max(0.0, $h); }

    /** Límite Y hasta donde es seguro dibujar (por encima de la reserva inferior) */
    protected function usableBottomY(): float {
        return $this->GetPageHeight() - $this->bMargin - $this->reservedBottom;
    }

    /** ¿La siguiente “altura” se pasa del límite utilizable? */
    protected function willExceed(float $deltaHeight): bool {
        return ($this->GetY() + $deltaHeight) > $this->usableBottomY();
    }

    /** Cuenta líneas estimadas de MultiCell (clásico FPDF) */
    protected function nbLines(float $w, string $txt): int {
        $cw = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb-1] === "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c === ' ') $sep = $i;
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep === -1) { if ($i === $j) $i++; }
                else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else { $i++; }
        }
        return $nl;
    }

    // =======================
    // Bloques de contenido
    // =======================
    public function addParagraph(string $text): void
    {
        $this->SetFont('Arial','',9);
        $this->SetTextColor(...self::COLOR_TEXT);
        $this->MultiCell(0, 5.8, self::enc($text), 0, 'J');
        $this->Ln(2);
    }

    /**
     * Dibuja la tabla de movimientos con el header (repite encabezados al saltar de página)
     * y calcula totales. No invade la reserva inferior.
     *
     * @param array $headers Nombres de columnas (8): ["#","Fecha","Clase","Número","Tipo de movimiento","Monto","Nombre y Apellido","Observaciones"]
     * @param array $rows    Cada fila: [num,fecha,clase,numero,tipo,monto,persona,obs]
     * @param float $sigReserve Alto (mm) reservado para la zona de firmas (p.ej. 120)
     * @return array [totalIn, totalOut, stock]
     */
    // public function addMovementsTable(array $headers, array $rows, float $sigReserve = 120.0): array
    public function addMovementsTable(array $headers, array $rows): array
    {
        // Ancho útil de la tabla
        $tableW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;

        // Anchos proporcionales (ajusta si quieres)
        $w = [
            $tableW * 0.05, // #
            $tableW * 0.10, // Fecha
            $tableW * 0.12, // Clase
            $tableW * 0.15, // Número
            $tableW * 0.12, // Tipo
            $tableW * 0.10, // Monto
            $tableW * 0.21, // Persona (Nombres)
            $tableW * 0.15, // Observaciones
        ];

        $lineH = 7.0;      // alto de línea para MultiCell
        $padX  = 1.2;      // padding lateral para celdas con wrap
        $padY  = 1.0;      // padding superior para celdas con wrap

        // Encabezado (se reimprime al saltar de página)
        $printHeader = function() use ($headers, $w) {
            $this->SetFillColor(230,235,255);
            $this->SetTextColor(30,30,60);
            // $this->SetDrawColor(200,205,235);
            $this->SetDrawColor(...self::COLOR_TABLE_BORDER);
            $this->SetLineWidth(0.2);
            $this->SetFont('Arial','B',10);
            foreach ($headers as $i => $h) {
                $this->Cell($w[$i], 8, self::enc($h), 1, 0, 'C', true);
            }
            $this->Ln();
            $this->SetFont('Arial','',9);
            $this->SetTextColor(50,50,50);
        };

        $printHeader();

        $totalIn = 0.0; 
        $totalOut = 0.0;

        foreach ($rows as $r) {
            [$num,$fecha,$clase,$numero,$tipo,$monto,$persona,$obs] = $r;

            // Textos (Nombres y Observaciones envuelven)
            $namesText = self::enc((string)($persona ?: 'Julia Mamani Yampasi')); // col 6
            $obsText   = self::enc((string)$obs);                                  // col 7

            // Altura de la fila = máx líneas envueltas
            $namesLines = $this->nbLines($w[6], $namesText);
            $obsLines   = $this->nbLines($w[7], $obsText);
            $rowH       = max($lineH, $namesLines * $lineH, $obsLines * $lineH);

            // Salto de página si no cabe la fila completa
            $contentBottomY = $this->GetPageHeight() - $this->bMargin;
            if (($this->GetY() + $rowH) > $contentBottomY) {
                $this->AddPage();
                $printHeader();
            }

            // Zebra
            $fill = ((int)$num % 2 === 0);
            $this->SetFillColor($fill?248:255, $fill?248:255, $fill?255:255);

            $x0 = $this->GetX(); 
            $y0 = $this->GetY();

            // Columnas 0..5 (sin wrap) con altura de fila completa
            $this->Cell($w[0], $rowH, (string)$num,               1, 0, 'C', $fill);
            $this->Cell($w[1], $rowH, self::enc((string)$fecha),  1, 0, 'L', $fill);
            $this->Cell($w[2], $rowH, self::enc((string)$clase),  1, 0, 'C', $fill);
            $this->Cell($w[3], $rowH, self::enc((string)$numero), 1, 0, 'C', $fill);
            $this->Cell($w[4], $rowH, self::enc((string)$tipo),   1, 0, 'C', $fill);
            $this->Cell($w[5], $rowH, number_format((float)$monto, 2), 1, 0, 'R', $fill);

            // Columna 6: Nombres (wrap) → Rect a alto de fila + MultiCell sin borde
            $xN = $this->GetX(); $yN = $y0;

            // Altura real del texto envuelto en la celda
            $namesTextH   = max($lineH, $namesLines * $lineH);
            $namesOffsetY = max($padY, ($rowH - $namesTextH) / 2);
            $namesOffsetY = min($namesOffsetY, $rowH - $padY - $namesTextH);

            // Marco al alto de la fila (unifica borde/relleno, evita “línea interna”)
            $this->Rect($xN, $yN, $w[6], $rowH, $fill ? 'DF' : 'D');

            // Texto envuelto, sin borde, centrado vertical
            $this->SetXY($xN + $padX, $yN + $namesOffsetY);
            $this->MultiCell($w[6] - 2*$padX, $lineH, $namesText, 0, 'L');

            // Cursor al borde derecho de la celda
            $this->SetXY($xN + $w[6], $yN);

            // Columna 7: Observaciones (wrap) → mismo patrón
            $xO = $this->GetX(); $yO = $y0;

            $obsTextH   = max($lineH, $obsLines * $lineH);
            $obsOffsetY = max($padY, ($rowH - $obsTextH) / 2);
            $obsOffsetY = min($obsOffsetY, $rowH - $padY - $obsTextH);

            $this->Rect($xO, $yO, $w[7], $rowH, $fill ? 'DF' : 'D');
            $this->SetXY($xO + $padX, $yO + $obsOffsetY);
            $this->MultiCell($w[7] - 2*$padX, $lineH, $obsText, 0, 'L');

            // Cerrar la fila
            $this->SetXY($x0, $y0 + $rowH);

            // Totales
            if (strtolower((string)$tipo) === 'entrada') $totalIn  += (float)$monto;
            if (strtolower((string)$tipo) === 'salida')  $totalOut += (float)$monto;
        }

        $stock = $totalIn - $totalOut;

        // Bloque de totales: si no caben 2 filas, pasar de página
        $hRow = 8.0;
        $contentBottomY = $this->GetPageHeight() - $this->bMargin;
        if (($this->GetY() + $hRow*2) > $contentBottomY) {
            $this->AddPage();
        }
        // $this->Cell(, $hRow, '', 0, 0, 'L', false);
        $this->addTotalsBlock($totalIn, $totalOut, $stock);

        return compact('totalIn','totalOut','stock');
    }



    /** Bloque de totales (similar a la Blade): 2 filas + caja grande a la izquierda */
    public function addTotalsBlock(float $totalIn, float $totalOut, float $stock): void
    {
        $tableW = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $wLeft  = $tableW * 0.60;
        $wEach  = $tableW * 0.133;
        $hRow   = 8.0;

        // === ESPACIO entre la tabla y los totales ===
        $gapTop = 4.0; // mm (ajusta aquí)
        if ($this->willExceed($gapTop + $hRow*2)) {
            // Si no cabe el gap + 2 filas de totales, pasa de página
            $this->AddPage();
        } else {
            $this->Ln($gapTop);
        }

        // Fila 1: encabezados (la celda izquierda es una caja grande en blanco)
        $this->SetFont('Arial','B',10);
        // $this->SetDrawColor(128, 128, 128);
        // $this->SetDrawColor(...self::COLOR_TABLE_BORDER);
        // dentro de addTotalsBlock():
        $this->SetDrawColor(...self::COLOR_TABLE_BORDER);


        $this->SetFillColor(248,248,255);
        // $this->SetDrawColor(0,0,0);

        $this->Cell($wLeft, $hRow, '', 0, 0, 'L', false);
        $this->Cell($wEach, $hRow, self::enc('Total entrada'), 1, 0, 'C', true);
        $this->Cell($wEach, $hRow, self::enc('Total salida'),  1, 0, 'C', true);
        $this->Cell($wEach, $hRow, self::enc('Stock'),         1, 1, 'C', true);

        // Fila 2: valores
        $this->SetFont('Arial','',10);
        $this->Cell($wLeft, $hRow, '', 0, 0, 'L', false);
        $this->Cell($wEach, $hRow, number_format($totalIn, 2),  1, 0, 'C', false);
        $this->Cell($wEach, $hRow, number_format($totalOut, 2), 1, 0, 'C', false);
        $this->Cell($wEach, $hRow, number_format($stock, 2),    1, 1, 'C', false);

        $this->Ln(2);
    }

    /**
     * Zona de firmas en la ÚLTIMA página. 4 cajas, etiquetas centradas abajo.
     * Se coloca por encima del margen inferior respetando el alto $height.
     */
    // public function placeSignatureBoxesBottom(
    //     float $height = 120.0,
    //     array $labels = ['ALMACENERO','ADMINISTRADOR','RESIDENTE DE OBRA','SUPERVISOR'],
    //     float $gap = 5.0
    // ): void
    public function placeSignatureBoxesBottom(
        float $height = 32.0,
        array $labels = ['ALMACENERO','ADMINISTRADOR','RESIDENTE DE OBRA','SUPERVISOR'],
        float $gap = 0.0 // ← sin espacio entre cajas
    ): void
    {

        $height = 40.0;
        // Geometría
        $pageW = $this->GetPageWidth();
        $pageH = $this->GetPageHeight();

        $x = $this->lMargin;
        $usableW = $pageW - $this->lMargin - $this->rMargin;

        // Borde superior del bloque (encima del footer)
        $yTop = $pageH - $this->bMargin - $height;

        // Si no hay espacio, crear última página
        if ($this->GetY() > ($yTop - 2)) {
            $this->AddPage();
            $pageW = $this->GetPageWidth();
            $pageH = $this->GetPageHeight();
            $x = $this->lMargin;
            $usableW = $pageW - $this->lMargin - $this->rMargin;
            $yTop = $pageH - $this->bMargin - $height;
        }

        $n = max(1, count($labels));
        $gap = 0.0; // fuerza cero separación
        $boxW = ($usableW - $gap * ($n - 1)) / $n;
        


        $this->SetDrawColor(220, 220, 220);

        $this->SetLineWidth(0.2);
        $this->SetFont('Arial','B',10);

        for ($i = 0; $i < $n; $i++) {
            $xi = $x + $i * ($boxW + $gap);
            // Ajuste del último ancho para evitar micro-grietas por redondeo
            $currW = ($i === $n - 1) ? ($x + $usableW - $xi) : $boxW;

            // Marco del recuadro
            $this->Rect($xi, $yTop, $currW, $height, 'D');

            // (Eliminado) línea de firma
            // $lineY = $yTop + $height - 12;  // ← eliminado
            // $this->Line($xi + 15, $lineY, $xi + $currW - 15, $lineY);

            // Etiqueta centrada abajo
            $this->SetXY($xi, $yTop + $height - 10);
            $this->Cell($currW, 6, self::enc((string)$labels[$i]), 0, 0, 'C');
        }
    }

    // =======================
    // Método de alto nivel
    // =======================
    /**
     * Render completo. Devuelve los bytes del PDF.
     * payload:
     *  - columns: array encabezados (8)
     *  - movementsRows: array de filas
     *  - sigReserveHeight: mm (default 120)
     *  - signatureLabels: array de 4 etiquetas (opcional)
     *  - signatureBoxHeight: mm (default 120)
     *  - intro: string (opcional)
     */
    public function render(array $payload): string
    {
        $this->AddPage();

        if (!empty($payload['intro'])) {
            $this->addParagraph($payload['intro']);
        }

        $this->addMovementsTable(
            $payload['columns'] ?? ['#','Fecha','Clase','Número','Tipo de movimiento','Monto','Nombre y Apellido','Observaciones'],
            $payload['movementsRows'] ?? []
        );

        $this->placeSignatureBoxesBottom(
            (float)($payload['signatureBoxHeight'] ?? 32.0), // ~32mm ≈ 120px
            $payload['signatureLabels'] ?? ['ALMACENERO','ADMINISTRADOR','RESIDENTE DE OBRA','SUPERVISOR'],
            5.0
        );

        return $this->Output('S');
    }

    public static function px(float $px, float $dpi = 96): float {
        // 1in = 25.4mm; dompdf usa 96dpi por defecto
        return $px * 25.4 / $dpi;
    }

}
