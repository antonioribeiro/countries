<?php

namespace CommerceGuys\Tax\Tests\Resolver;

use CommerceGuys\Tax\Resolver\TaxRate\DefaultTaxRateResolver;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Resolver\TaxRate\DefaultTaxRateResolver
 */
class DefaultTaxRateResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->resolver = new DefaultTaxRateResolver();
    }

    /**
     * @covers ::resolve
     *
     * @uses \CommerceGuys\Tax\Model\TaxType
     * @uses \CommerceGuys\Tax\Model\TaxRate
     */
    public function testResolver()
    {
        $reducedRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->getMock();
        $reducedRate->expects($this->any())
            ->method('isDefault')
            ->will($this->returnValue(false));
        $standardRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->getMock();
        $standardRate->expects($this->any())
            ->method('isDefault')
            ->will($this->returnValue(true));

        // Confirm that the default tax rate is returned.
        $taxType = $this->getTaxType([$reducedRate, $standardRate]);
        $taxable = $this
            ->getMockBuilder('CommerceGuys\Tax\TaxableInterface')
            ->getMock();
        $context = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->resolver->resolve($taxType, $taxable, $context);
        $this->assertSame($standardRate, $result);

        // Confirm that null is returned when no default rate exists.
        $taxType = $this->getTaxType([$reducedRate]);
        $result = $this->resolver->resolve($taxType, $taxable, $context);
        $this->assertNull($result);
    }

    /**
     * Returns a mock tax type with the given rates.
     *
     * @param array $rates The tax rates.
     *
     * @return \CommerceGuys\Tax\Model\TaxType
     */
    protected function getTaxType($rates)
    {
        $taxType = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxType')
            ->getMock();
        $taxType->expects($this->any())
            ->method('getRates')
            ->will($this->returnValue($rates));

        return $taxType;
    }
}
