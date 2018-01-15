<?php

namespace CommerceGuys\Tax\Tests\Repository;

use CommerceGuys\Tax\Enum\GenericLabel;
use CommerceGuys\Tax\Repository\TaxTypeRepository;
use org\bovigo\vfs\vfsStream;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Repository\TaxTypeRepository
 */
class TaxTypeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Known tax types.
     *
     * @var array
     */
    protected $taxTypes = [
        'fr_vat' => [
            'id' => 'fr_vat',
            'name' => 'French VAT',
            'generic_label' => 'vat',
            'display_inclusive' => true,
            'zone' => 'fr',
            'tag' => 'EU',
            'rates' => [
                [
                    'id' => 'fr_vat_standard',
                    'name' => 'Standard',
                    'default' => true,
                    'amounts' => [
                        [
                            'id' => 'fr_vat_standard_196',
                            'amount' => 0.196,
                            'start_date' => '2004-04-01',
                            'end_date' => '2013-12-31',
                        ],
                        [
                            'id' => 'fr_vat_standard_20',
                            'amount' => 0.2,
                            'start_date' => '2014-01-01',
                        ],
                    ],
                ],
            ],
        ],
        'de_vat' => [
            'id' => 'de_vat',
            'name' => 'German VAT',
            'generic_label' => 'vat',
            'zone' => 'de',
            'tag' => 'EU',
            'rates' => [
                [
                    'id' => 'de_vat_standard',
                    'name' => 'Standard',
                    'default' => true,
                    'amounts' => [
                        [
                            'id' => 'de_vat_standard_19',
                            'amount' => 0.19,
                            'start_date' => '2007-01-01',
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * The tax repository.
     *
     * @var TaxTypeRepository
     */
    protected $taxTypeRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        // Mock the existence of JSON definitions on the filesystem.
        $root = vfsStream::setup('resources');
        $directory = vfsStream::newDirectory('tax_type')->at($root);
        foreach ($this->taxTypes as $id => $definition) {
            $filename = $id . '.json';
            vfsStream::newFile($filename)->at($directory)->setContent(json_encode($definition));
        }

        $zoneRepository = $this->getZoneRepository();
        $this->taxTypeRepository = new TaxTypeRepository('vfs://resources/', $zoneRepository);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        // Note: other tests use $this->dataProvider instead of depending on
        // testConstructor because of a phpunit bug with dependencies and mocks:
        // https://github.com/sebastianbergmann/phpunit-mock-objects/issues/127
        $zoneRepository = $this->getZoneRepository();
        $taxTypeRepository = new TaxTypeRepository('vfs://resources/', $zoneRepository);
        $setZoneRepository = $this->getObjectAttribute($taxTypeRepository, 'zoneRepository');
        $this->assertSame($setZoneRepository, $zoneRepository);
    }

    /**
     * @covers ::get
     * @covers ::loadDefinition
     * @covers ::createTaxTypeFromDefinition
     * @covers ::createTaxRateFromDefinition
     * @covers ::createTaxRateAmountFromDefinition
     *
     * @uses \CommerceGuys\Tax\Repository\TaxTypeRepository::__construct
     * @uses \CommerceGuys\Tax\Model\TaxType
     * @uses \CommerceGuys\Tax\Model\TaxRate
     * @uses \CommerceGuys\Tax\Model\TaxRateAmount
     */
    public function testGet()
    {
        $taxType = $this->taxTypeRepository->get('fr_vat');
        $this->assertInstanceOf('CommerceGuys\Tax\Model\TaxType', $taxType);
        $this->assertInstanceOf('CommerceGuys\Zone\Model\Zone', $taxType->getZone());
        $this->assertEquals('fr_vat', $taxType->getId());
        $this->assertEquals('French VAT', $taxType->getName());
        $this->assertEquals(GenericLabel::VAT, $taxType->getGenericLabel());
        $this->assertEquals(true, $taxType->isDisplayInclusive());
        $this->assertEquals('EU', $taxType->getTag());
        $rates = $taxType->getRates();
        $this->assertCount(1, $rates);

        $rate = $rates[0];
        $this->assertInstanceOf('CommerceGuys\Tax\Model\TaxRate', $rate);
        $this->assertEquals($taxType, $rate->getType());
        $this->assertEquals('fr_vat_standard', $rate->getId());
        $this->assertEquals('Standard', $rate->getName());
        $this->assertEquals(true, $rate->isDefault());
        $amounts = $rate->getAmounts();
        $this->assertCount(2, $amounts);

        $amount = $amounts[0];
        $this->assertInstanceOf('CommerceGuys\Tax\Model\TaxRateAmount', $amount);
        $this->assertEquals($rate, $amount->getRate());
        $this->assertEquals('fr_vat_standard_196', $amount->getId());
        $this->assertEquals(0.196, $amount->getAmount());
        $this->assertEquals(new \DateTime('2004-04-01'), $amount->getStartDate());
        $this->assertEquals(new \DateTime('2013-12-31'), $amount->getEndDate());

        // Test the static cache.
        $sameTaxType = $this->taxTypeRepository->get('fr_vat');
        $this->assertSame($taxType, $sameTaxType);
    }

    /**
     * @covers ::get
     * @covers ::loadDefinition
     *
     * @uses \CommerceGuys\Tax\Repository\TaxTypeRepository::__construct
     * @expectedException \CommerceGuys\Tax\Exception\UnknownTaxTypeException
     */
    public function testGetNonExistingTaxType()
    {
        $this->taxTypeRepository->get('es_vat');
    }

    /**
     * @covers ::getAll
     * @covers ::loadDefinition
     * @covers ::createTaxTypeFromDefinition
     * @covers ::createTaxRateFromDefinition
     * @covers ::createTaxRateAmountFromDefinition
     *
     * @uses \CommerceGuys\Tax\Repository\TaxTypeRepository::__construct
     * @uses \CommerceGuys\Tax\Repository\TaxTypeRepository::get
     * @uses \CommerceGuys\Tax\Model\TaxType
     * @uses \CommerceGuys\Tax\Model\TaxRate
     * @uses \CommerceGuys\Tax\Model\TaxRateAmount
     */
    public function testGetAll()
    {
        $taxTypes = $this->taxTypeRepository->getAll();
        $this->assertCount(2, $taxTypes);
        $this->assertArrayHasKey('fr_vat', $taxTypes);
        $this->assertArrayHasKey('de_vat', $taxTypes);
        $this->assertEquals($taxTypes['fr_vat']->getId(), 'fr_vat');
        $this->assertEquals($taxTypes['de_vat']->getId(), 'de_vat');
    }

    /**
     * Returns a mock zone repository.
     *
     * @return \CommerceGuys\Zone\Repository\ZoneRepository
     */
    protected function getZoneRepository()
    {
        $zone = $this->getMock('CommerceGuys\Zone\Model\Zone');
        $zoneRepository = $this
            ->getMockBuilder('CommerceGuys\Zone\Repository\ZoneRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $zoneRepository
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue($zone));

        return $zoneRepository;
    }
}
