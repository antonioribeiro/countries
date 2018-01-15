<?php

namespace CommerceGuys\Tax\Resolver\TaxType;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Resolver\Context;

/**
 * Tax type resolver interface.
 */
interface TaxTypeResolverInterface
{
    // Stops resolving when there is no applicable tax type (cause the customer
    // is a US non-profit, for example).
    const NO_APPLICABLE_TAX_TYPE = 'no_applicable_tax_type';

    /**
     * Returns the applicable tax types for the given taxable object.
     *
     * @param TaxableInterface $taxable The taxable object.
     * @param Context          $context The context.
     *
     * @return mixed An array of tax types, or NO_APPLICABLE_TAX_TYPE.
     */
    public function resolve(TaxableInterface $taxable, Context $context);
}
