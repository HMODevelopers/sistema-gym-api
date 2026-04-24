<?php

namespace App\Enums;

enum ClienteEstatus: string
{
    case ACTIVO = 'ACTIVO';
    case SUSPENDIDO = 'SUSPENDIDO';
    case BLOQUEADO = 'BLOQUEADO';
    case INACTIVO = 'INACTIVO';
}
