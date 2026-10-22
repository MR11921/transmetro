<?php

namespace App\Exceptions;

use RuntimeException;

/** Se lanza cuando una operación viola una regla de negocio (RN-xxx). */
class ReglaNegocioException extends RuntimeException
{
}
