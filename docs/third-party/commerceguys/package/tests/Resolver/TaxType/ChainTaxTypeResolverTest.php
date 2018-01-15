<?php

namespace CommerceGuys\Tax\Tests\Resolver\TaxType;

use CommerceGuys\Tax\Resolver\TaxType\ChainTaxTypeResolver;
use CommerceGuys\Tax\Resolver\TaxType\TaxTypeResolverInterface;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Resolver\TaxType\ChainTaxTypeResolver
 */
class ChainTaxTypeResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChainTaxTypeResolver
     */
    protected $chainResolver;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->chainResolver = new ChainTaxTypeResolver();
    }

    /**
     * @covers ::addResolver
     * @covers ::getResolvers
     * @covers ::resolve
     * @covers \CommerceGuys\Tax\Resolver\ResolverSorterTrait::sortResolvers
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testResolve()
    {
        $firstTaxType = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxType')
            ->getMock();
        $secondTaxType = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxType')
            ->getMock();
        $firstResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxType\TaxTypeResolverInterface')
            ->getMock();
        $secondResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxType\TaxTypeResolverInterface')
            ->getMock();
        $secondResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue([$firstTaxType]));
        $thirdResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxType\TaxTypeResolverInterface')
            ->getMock();
        $thirdResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue([$secondTaxType]));
        $fourthResolver = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\TaxType\TaxTypeResolverInterface')
            ->getMock();
        $fourthResolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue(TaxTypeResolverInterface::NO_APPLICABLE_TAX_TYPE));

        $this->chainResolver->addResolver($firstResolver, 10);
        $this->chainResolver->addResolver($secondResolver);
        $this->chainResolver->addResolver($thirdResolver, 5);

        // Confirm that the added resolvers have been ordered by priority.
        $expectedResolvers = [$firstResolver, $thirdResolver, $secondResolver];
        $this->assertEquals($expectedResolvers, $this->chainResolver->getResolvers());

        $taxable = $this
            ->getMockBuilder('CommerceGuys\Tax\TaxableInterface')
            ->getMock();
        $context = $this
            ->getMockBuilder('CommerceGuys\Tax\Resolver\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $result = $this->chainResolver->resolve($taxable, $context);
        $this->assertSame([$secondTaxType], $result);

        // The new resolver will run first, and return NO_APPLICABLE_TAX_TYPE,
        // which should cause the resolving to stop and an empty array to be
        // returned.
        $this->chainResolver->addResolver($fourthResolver, 10);
        $result = $this->chainResolver->resolve($taxable, $context);
        $this->assertEmpty($result);
    }
}
