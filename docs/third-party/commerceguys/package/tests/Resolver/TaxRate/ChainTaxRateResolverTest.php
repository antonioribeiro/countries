<?php

namespace CommerceGuys\Tax\Tests\Resolver\TaxRate;

use CommerceGuys\Tax\Resolver\TaxRate\ChainTaxRateResolver;
use CommerceGuys\Tax\Resolver\TaxRate\TaxRateResolverInterface;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Resolver\TaxRate\ChainTaxRateResolver
 */
class ChainTaxRateResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainTaxRateResolver
     */
    protected $chainResolver;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->chainResolver = new ChainTaxRateResolver();
    }

    /**
     * @covers ::addResolver
     * @covers ::getResolvers
     * @covers ::resolve
     * @covers \CommerceGuys\Tax\Resolver\ResolverSorterTrait::sortResolvers
     *
     * @uses \CommerceGuys\Tax\Model\TaxRate::__construct
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testResolve()
    {
        $firstTaxRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->getMock();
        $secondTaxRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->getMock();
        $firstResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxRate\TaxRateResolverInterface')
            ->getMock();
        $secondResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxRate\TaxRateResolverInterface')
            ->getMock();
        $secondResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue($firstTaxRate));
        $thirdResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxRate\TaxRateResolverInterface')
            ->getMock();
        $thirdResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue($secondTaxRate));
        $fourthResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxRate\TaxRateResolverInterface')
            ->getMock();
        $fourthResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue(TaxRateResolverInterface::NO_APPLICABLE_TAX_RATE));

        $this->chainResolver->addResolver($firstResolver, 10);
        $this->chainResolver->addResolver($secondResolver);
        $this->chainResolver->addResolver($thirdResolver, 5);

        // Confirm that the added resolvers have been ordered by priority.
        $expectedResolvers = [$firstResolver, $thirdResolver, $secondResolver];
        $this->assertEquals($expectedResolvers, $this->chainResolver->getResolvers());

        $taxType = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxType')
            ->getMock();
        $taxable = $this
            ->getMockBuilder('CommerceGuys\Tax\TaxableInterface')
            ->getMock();
        $context = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->chainResolver->resolve($taxType, $taxable, $context);
        $this->assertSame($secondTaxRate, $result);

        // The new resolver will run first, and return NO_APPLICABLE_TAX_RATE,
        // which should cause the resolving to stop and null to be returned.
        $this->chainResolver->addResolver($fourthResolver, 10);
        $result = $this->chainResolver->resolve($taxType, $taxable, $context);
        $this->assertNull($result);
    }
}
