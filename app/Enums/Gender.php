<?php

declare(strict_types=1);

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case NonBinary = 'non_binary';
    case NotSpecified = 'not_specified';
}
