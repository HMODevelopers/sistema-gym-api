<?php

namespace App\Enums;

enum DispositivoTipo: string
{
    case PC_RECEPCION = 'PC_RECEPCION';
    case LECTOR_HUELLA = 'LECTOR_HUELLA';
    case TABLET = 'TABLET';
    case KIOSKO = 'KIOSKO';
    case OTRO = 'OTRO';
}
