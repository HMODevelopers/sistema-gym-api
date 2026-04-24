<?php

namespace App\Enums;

enum ClienteBiometricoEstatus: string
{
    case ACTIVO = 'ACTIVO';
    case INACTIVO = 'INACTIVO';
    case REVOCADO = 'REVOCADO';
}
