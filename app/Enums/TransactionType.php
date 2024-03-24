<?php

namespace App\Enums;

use App\Enums\Traits\HasKeyValue;

enum TransactionType: int
{
    use HasKeyValue;

    case INCOME = 10;
    case EXPENSE = 30;
}
