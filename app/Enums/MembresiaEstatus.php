<?php

namespace App\Enums;

enum MembresiaEstatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case VIGENTE = 'VIGENTE';
    case VENCIDA = 'VENCIDA';
    case SUSPENDIDA = 'SUSPENDIDA';
    case CANCELADA = 'CANCELADA';
    case AGOTADA = 'AGOTADA';
}
