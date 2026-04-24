<?php

namespace App\Enums;

enum UsuarioEstatus: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
    case BLOQUEADO = 'BLOQUEADO';
}
