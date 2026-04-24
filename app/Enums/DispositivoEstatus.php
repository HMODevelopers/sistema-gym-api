<?php

namespace App\Enums;

enum DispositivoEstatus: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
    case MANTENIMIENTO = 'MANTENIMIENTO';
}
