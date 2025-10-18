<?php

namespace App\Exceptions;

/**
 *
 */
class OutOfBoundValueException extends \Exception
{
    protected $message = 'O valor precisar ser maior que '
        . 'R$ 1.000,00 e menor que R$ 100.000,00';

    public int $status = 406;
}
