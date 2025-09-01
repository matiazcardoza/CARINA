<?php

namespace App\Utils;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class UsefulFunctionsForPdfs
{

    public static function calcularEdad(string $fechaNacimiento): int
    {
        return "hola mundo";
    }

    public static function SetNameForPDF(string $fechaNacimiento): int
    {
        return "hola mundo";
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