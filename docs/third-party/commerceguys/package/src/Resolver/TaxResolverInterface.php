<?php

namespace CommerceGuys\Tax\Resolver;

use CommerceGuys\Tax\TaxableInterface;

/**
 * Tax resolver interface.
 *
 * Acts as a facade in front of the chain resolvers and serves as the
 * single point of contact between the resolving system and the outside world.
 */
interface TaxResolverInterface
{
    /**
     * Returns the applicable tax rate amounts for the given taxable object.
     *
     * @param TaxableInterface $taxable The taxable object.
     * @param Context          $context The context.
     *
     * @return TaxRateAmountInterface[] An array of resolved tax rate amounts.
     */
    public function resolveAmounts(TaxableInterface $taxable, Context $context);

    /**
     * Returns the applicable tax rates for the given taxable object.
     *
     * @param TaxableInterface $taxable The taxable object.
     * @param Context          $context The context.
     *
     * @return TaxRateInterface[] An array of resolved tax rates.
     */
    public function resolveRates(TaxableInterface $taxable, Context $context);

    /**
     * Returns the applicable tax types for the given taxable object.
     *
     * @param TaxableInterface $taxable The taxable object.
     * @param Context          $context The context.
     *
     * @return TaxTypeInterface[] An array of resolved tax types.
     */
    public function resolveTypes(TaxableInterface $taxable, Context $context);
}
