<?php

namespace CommerceGuys\Tax\Resolver;

use CommerceGuys\Tax\TaxableInterface;
use CommerceGuys\Tax\Resolver\TaxRate\ChainTaxRateResolverInterface;
use CommerceGuys\Tax\Resolver\TaxType\ChainTaxTypeResolverInterface;

class TaxResolver implements TaxResolverInterface
{
    /**
     * The chain tax type resolver.
     *
     * @var ChainTaxTypeResolverInterface
     */
    protected $chainTaxTypeResolver;

    /**
     * The chain tax rate resolver.
     *
     * @var ChainTaxRateResolverInterface
     */
    protected $chainTaxRateResolver;

    /**
     * Creates a TaxResolver instance.
     *
     * @param ChainTaxTypeResolverInterface $chainTaxTypeResolver
     * @param ChainTaxRateResolverInterface $chainTaxRateResolver
     */
    public function __construct($chainTaxTypeResolver, $chainTaxRateResolver)
    {
        $this->chainTaxTypeResolver = $chainTaxTypeResolver;
        $this->chainTaxRateResolver = $chainTaxRateResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveAmounts(TaxableInterface $taxable, Context $context)
    {
        $date = $context->getDate();
        $rates = $this->resolveRates($taxable, $context);
        $amounts = [];
        foreach ($rates as $rate) {
            $amounts[] = $rate->getAmount($date);
        }

        return $amounts;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRates(TaxableInterface $taxable, Context $context)
    {
        $types = $this->resolveTypes($taxable, $context);
        $rates = [];
        foreach ($types as $type) {
            $rate = $this->chainTaxRateResolver->resolve($type, $taxable, $context);
            if ($rate) {
                $rates[] = $rate;
            }
        }

        return $rates;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveTypes(TaxableInterface $taxable, Context $context)
    {
        return $this->chainTaxTypeResolver->resolve($taxable, $context);
    }
}
