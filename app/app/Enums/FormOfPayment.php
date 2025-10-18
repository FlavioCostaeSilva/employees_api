<?php

namespace App\Enums;

enum FormOfPayment: string
{
    case BOLETO = 'BOLETO';
    case CREDIT_CARD = 'CREDIT_CARD';
}
