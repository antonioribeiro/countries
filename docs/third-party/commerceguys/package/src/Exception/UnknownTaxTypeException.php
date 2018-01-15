<?php

namespace CommerceGuys\Tax\Exception;

/**
 * Thrown when an unknown tax type id is passed to the TaxTypeRepository.
 */
class UnknownTaxTypeException extends \InvalidArgumentException implements ExceptionInterface
{
}
