<?php
declare(strict_types=1);

namespace App\Enums;

enum ProductOperationEnum: string
{
    case DELETE = 'delete';
    case CREATE = 'create';
    case UPDATE = 'update';
}
