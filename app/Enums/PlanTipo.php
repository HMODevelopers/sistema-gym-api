<?php

namespace App\Enums;

enum PlanTipo: string
{
    case SEMANAL = 'SEMANAL';
    case QUINCENAL = 'QUINCENAL';
    case MENSUAL = 'MENSUAL';
    case TRIMESTRAL = 'TRIMESTRAL';
    case ANUAL = 'ANUAL';
    case POR_ACCESOS = 'POR_ACCESOS';
    case PROMOCION = 'PROMOCION';
}
