<?php

namespace CommerceGuys\Tax\Enum;

use CommerceGuys\Enum\AbstractEnum;

/**
 * Enumerates available generic labels.
 *
 * @codeCoverageIgnore
 */
final class GenericLabel extends AbstractEnum
{
    const TAX = 'tax';
    const VAT = 'vat';
    const GST = 'gst';
    const PST = 'pst';
    const HST = 'hst';
    const CONSUMPTION_TAX = 'consumption_tax';

    /**
     * Gets the default value.
     *
     * @return string The default value.
     */
    public static function getDefault()
    {
        return static::TAX;
    }
}
