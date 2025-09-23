<?php

namespace App\Utils;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class UsefulFunctionsForPdfs
{

    public static function calcularEdad(string $fechaNacimiento): int
    {
        return "hola mundo";
    }

    public static function extractPdfFilename(string $pdfPath): ?string
    {
        // 1) Intentar ?name=archivo.pdf
        $query = parse_url($pdfPath, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        $filename = $params['name'] ?? null;

        // 2) Si no viene en query, tomar del path
        if (!$filename) {
            $path = parse_url($pdfPath, PHP_URL_PATH) ?? $pdfPath;
            $filename = basename($path);
        }

        // 3) Normalizar y validar (evita traversal y exige .pdf)
        $filename = trim(str_replace(['\\', '/'], '', (string)$filename));
        if (!preg_match('/^[A-Za-z0-9._-]+\.pdf$/', $filename)) {
            return null; // inválido
        }

        return $filename;
    }

    public static function generateQRcodeInBinariFormat($contenido){

        return  QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($contenido);
    }

    // guarda la imagen del qr y retorna la url donde esta guardado
    public static function generateQRcode($texto = '') {
        $pngData = QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($texto);

        $tmpFile = tempnam(sys_get_temp_dir(), 'qr_');
        rename($tmpFile, $tmpFile .= '.png');
        file_put_contents($tmpFile, $pngData);
        return $tmpFile;
    }


}