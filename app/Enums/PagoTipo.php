<?php

namespace App\Enums;

enum PagoTipo: string
{
    case INSCRIPCION = 'INSCRIPCION';
    case MEMBRESIA = 'MEMBRESIA';
    case RENOVACION = 'RENOVACION';
    case PRODUCTO = 'PRODUCTO';
    case SERVICIO = 'SERVICIO';
    case AJUSTE = 'AJUSTE';
}
