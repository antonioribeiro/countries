<?php

namespace CommerceGuys\Tax\Resolver\TaxRate;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Model\TaxTypeInterface;
use CommerceGuys\Tax\Resolver\Context;

/**
 * Tax rate resolver interface.
 */
interface TaxRateResolverInterface
{
    // Stops resolving when there is no applicable tax rate (cause the provided
    // taxable object is exempt from sales tax, for example).
    const NO_APPLICABLE_TAX_RATE = 'no_applicable_tax_rate';

    /**
     * Returns the applicable tax rate for the given tax type and taxable object.
     *
     * @param TaxTypeInterface $taxType A previously resolved tax type.
     * @param TaxableInterface $taxable The taxable object.
     * @param Context          $context The context.
     *
     * @return mixed The resolved rate, NO_APPLICABLE_TAX_RATE, or null.
     */
    public function resolve(TaxTypeInterface $taxType, TaxableInterface $taxable, Context $context);
}
