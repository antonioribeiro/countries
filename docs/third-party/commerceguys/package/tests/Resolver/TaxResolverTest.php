<?php

namespace CommerceGuys\Tax\Tests\Resolver;

use CommerceGuys\Tax\Resolver\TaxResolver;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Resolver\TaxResolver
 */
class TaxResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $chainTaxTypeResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxType\ChainTaxTypeResolver')
            ->getMock();
        $chainTaxRateResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxRate\ChainTaxRateResolver')
            ->getMock();
        $resolver = new TaxResolver($chainTaxTypeResolver, $chainTaxRateResolver);
        $this->assertSame($chainTaxTypeResolver, $this->getObjectAttribute($resolver, 'chainTaxTypeResolver'));
        $this->assertSame($chainTaxRateResolver, $this->getObjectAttribute($resolver, 'chainTaxRateResolver'));
    }

    /**
     * @covers ::resolveAmounts
     * @covers ::resolveRates
     * @covers ::resolveTypes
     *
     * @uses \CommerceGuys\Tax\Resolver\TaxResolver::__construct
     * @uses \CommerceGuys\Tax\Model\TaxRate::__construct
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testResolver()
    {
        $firstTaxRateAmount = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRateAmount')
            ->getMock();
        $secondTaxRateAmount = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRateAmount')
            ->getMock();
        $firstTaxRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->getMock();
        $firstTaxRate->expects($this->any())
            ->method('getAmount')
            ->will($this->returnValue($firstTaxRateAmount));
        $secondTaxRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->getMock();
        $secondTaxRate->expects($this->any())
            ->method('getAmount')
            ->will($this->returnValue($secondTaxRateAmount));
        $firstTaxType = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxType')
            ->getMock();
        $secondTaxType = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxType')
            ->getMock();

        $chainTaxTypeResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxType\ChainTaxTypeResolver')
            ->getMock();
        $chainTaxTypeResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue([$firstTaxType, $secondTaxType]));
        $chainTaxRateResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxRate\ChainTaxRateResolver')
            ->getMock();
        $chainTaxRateResolver->expects($this->exactly(2))
            ->method('resolve')
            ->will($this->onConsecutiveCalls($firstTaxRate, $secondTaxRate));

        $resolver = new TaxResolver($chainTaxTypeResolver, $chainTaxRateResolver);
        $taxable = $this
            ->getMockBuilder('CommerceGuys\Tax\TaxableInterface')
            ->getMock();
        $context = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\Context')
            ->disableOriginalConstructor()
            ->getMock();
        // Since resolveAmounts calls resolveRates and resolveTypes, there
        // is no need to invoke them separately.
        $result = $resolver->resolveAmounts($taxable, $context);
        $this->assertEquals([$firstTaxRateAmount, $secondTaxRateAmount], $result);
    }
}
