<?php

namespace App\Enums;

enum AccesoMetodo: string
{
    case HUELLA = 'HUELLA';
    case QR = 'QR';
    case CODIGO = 'CODIGO';
    case BUSQUEDA_MANUAL = 'BUSQUEDA_MANUAL';
}
