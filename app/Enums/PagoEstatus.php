<?php

namespace App\Enums;

enum PagoEstatus: string
{
    case APLICADO = 'APLICADO';
    case CANCELADO = 'CANCELADO';
    case REEMBOLSADO = 'REEMBOLSADO';
}
