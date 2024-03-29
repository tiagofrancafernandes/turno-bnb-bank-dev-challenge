<?php

namespace App\Enums;

use App\Enums\Traits\HasKeyValue;

enum CheckStatus: int
{
    use HasKeyValue;

    case CREATED = 20;
    case WAITING = 30;
    case REJECTED = 40;
    case ACCEPTED = 50;
    case CANCELED = 60;
    case COMPLETED_SUCCESSFULLY = 70;
    case COMPLETED_WITH_ERROR = 80;
}
