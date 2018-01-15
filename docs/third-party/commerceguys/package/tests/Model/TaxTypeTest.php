<?php

namespace CommerceGuys\Tax\Tests\Model;

use CommerceGuys\Tax\Enum\GenericLabel;
use CommerceGuys\Tax\Model\TaxType;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Model\TaxType
 */
class TaxTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxType
     */
    protected $taxType;

    public function setUp()
    {
        $this->taxType = new TaxType();
    }

    /**
     * @covers ::getId
     * @covers ::setId
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testId()
    {
        $this->taxType->setId('de_vat');
        $this->assertEquals('de_vat', $this->taxType->getId());
    }

    /**
     * @covers ::getName
     * @covers ::setName
     * @covers ::__toString
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testName()
    {
        $this->taxType->setName('German VAT');
        $this->assertEquals('German VAT', $this->taxType->getName());
        $this->assertEquals('German VAT', (string) $this->taxType);
    }

    /**
     * @covers ::getGenericLabel
     * @covers ::setGenericLabel
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testGenericLabel()
    {
        $this->taxType->setGenericLabel(GenericLabel::VAT);
        $this->assertEquals(GenericLabel::VAT, $this->taxType->getGenericLabel());
    }

    /**
     * @covers ::isCompound
     * @covers ::setCompound
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testCompound()
    {
        $this->taxType->setCompound(true);
        $this->assertEquals(true, $this->taxType->isCompound());
    }

    /**
     * @covers ::isDisplayInclusive
     * @covers ::setDisplayInclusive
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testDisplayInclusive()
    {
        $this->taxType->setDisplayInclusive(true);
        $this->assertEquals(true, $this->taxType->isDisplayInclusive());
    }

    /**
     * @covers ::getRoundingMode
     * @covers ::setRoundingMode
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testRoundingMode()
    {
        $this->taxType->setRoundingMode(TaxType::ROUND_HALF_UP);
        $this->assertEquals(TaxType::ROUND_HALF_UP, $this->taxType->getRoundingMode());
    }

    /**
     * @covers ::getZone
     * @covers ::setZone
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testZone()
    {
        $zone = $this
            ->getMockBuilder('CommerceGuys\Zone\Model\Zone')
            ->getMock();

        $this->taxType->setZone($zone);
        $this->assertEquals($zone, $this->taxType->getZone());
    }

    /**
     * @covers ::getTag
     * @covers ::setTag
     *
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testTag()
    {
        $this->taxType->setTag('EU');
        $this->assertEquals('EU', $this->taxType->getTag());
    }

    /**
     * @covers ::__construct
     * @covers ::getRates
     * @covers ::setRates
     * @covers ::hasRates
     * @covers ::addRate
     * @covers ::removeRate
     * @covers ::hasRate
     *
     * @uses \CommerceGuys\Tax\Model\TaxRate::setType
     * @uses \CommerceGuys\Tax\Model\TaxType::__construct
     */
    public function testRates()
    {
        $firstTaxRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->disableOriginalConstructor()
            ->getMock();
        $secondTaxRate = $this
            ->getMockBuilder('CommerceGuys\Tax\Model\TaxRate')
            ->disableOriginalConstructor()
            ->getMock();
        $empty = new ArrayCollection();
        $rates = new ArrayCollection([$firstTaxRate, $secondTaxRate]);

        $this->assertEquals(false, $this->taxType->hasRates());
        $this->assertEquals($empty, $this->taxType->getRates());
        $this->taxType->setRates($rates);
        $this->assertEquals($rates, $this->taxType->getRates());
        $this->assertEquals(true, $this->taxType->hasRates());
        $this->taxType->removeRate($secondTaxRate);
        $this->assertEquals(false, $this->taxType->hasRate($secondTaxRate));
        $this->assertEquals(true, $this->taxType->hasRate($firstTaxRate));
        $this->taxType->addRate($secondTaxRate);
        $this->assertEquals($rates, $this->taxType->getRates());
    }
}
