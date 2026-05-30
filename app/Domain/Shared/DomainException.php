<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use RuntimeException;

/**
 * Base for all rule violations raised by the domain (illegal state transition,
 * modifying an order that is already cooking, etc.). Catching this single type
 * at the HTTP boundary lets the UI translate business errors into friendly
 * messages without leaking infrastructure exceptions.
 */
class DomainException extends RuntimeException
{
}
