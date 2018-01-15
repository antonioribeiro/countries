<?php

namespace CommerceGuys\Tax\Resolver\TaxType;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Resolver\Context;
use CommerceGuys\Tax\Resolver\ResolverSorterTrait;

class ChainTaxTypeResolver implements ChainTaxTypeResolverInterface
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
     * @var TaxTypeResolverInterface[]
     */
    protected $sortedResolvers = [];

    /**
     * {@inheritdoc}
     */
    public function addResolver(TaxTypeResolverInterface $resolver, $priority = 0)
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
    public function resolve(TaxableInterface $taxable, Context $context)
    {
        $result = [];
        $resolvers = $this->getResolvers();
        foreach ($resolvers as $resolver) {
            $result = $resolver->resolve($taxable, $context);
            if ($result) {
                break;
            }
        }
        // The NO_APPLICABLE_TAX_TYPE flag is used to stop further resolving,
        // but shouldn't be returned to the outside world.
        if ($result == TaxTypeResolverInterface::NO_APPLICABLE_TAX_TYPE) {
            $result = [];
        }

        return $result;
    }
}
