<?php

namespace CommerceGuys\Tax\Resolver\TaxRate;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Model\TaxTypeInterface;
use CommerceGuys\Tax\Resolver\Context;
use CommerceGuys\Tax\Resolver\ResolverSorterTrait;

class ChainTaxRateResolver implements ChainTaxRateResolverInterface
{
    use ResolverSorterTrait;

    /**
     * The resolvers.
     *
     * @var array
     */
    protected $resolvers = [];

    /**
     * The resolvers, sorted by priority.
     *
     * @var TaxRateResolverInterface[]
     */
    protected $sortedResolvers = [];

    /**
     * {@inheritdoc}
     */
    public function addResolver(TaxRateResolverInterface $resolver, $priority = 0)
    {
        $this->resolvers[] = [
            'resolver' => $resolver,
            'priority' => $priority,
        ];
        $this->sortedResolvers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getResolvers()
    {
        if (empty($this->sortedResolvers) && !empty($this->resolvers)) {
            $this->sortedResolvers = $this->sortResolvers($this->resolvers);
        }

        return $this->sortedResolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(TaxTypeInterface $taxType, TaxableInterface $taxable, Context $context)
    {
        $result = null;
        $resolvers = $this->getResolvers();
        foreach ($resolvers as $resolver) {
            $result = $resolver->resolve($taxType, $taxable, $context);
            if ($result) {
                break;
            }
        }
        // The NO_APPLICABLE_TAX_RATE flag is used to stop further resolving,
        // but shouldn't be returned to the outside world.
        if ($result == TaxRateResolverInterface::NO_APPLICABLE_TAX_RATE) {
            $result = null;
        }

        return $result;
    }
}
