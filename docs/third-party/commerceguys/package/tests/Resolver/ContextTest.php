<?php

namespace CommerceGuys\Tax\Tests\Resolver;

use CommerceGuys\Tax\Resolver\Context;

/**
 * @coversDefaultClass \CommerceGuys\Tax\Resolver\Context
 */
class ContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxType
     */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $address = $this
            ->getMockBuilder('CommerceGuys\Addressing\Address')
            ->getMock();
        $this->context = new Context($address, $address);
    }

    /**
     * @covers ::__construct
     *
     * @uses \CommerceGuys\Tax\Resolver\Context::getCustomerAddress
     * @uses \CommerceGuys\Tax\Resolver\Context::getStoreAddress
     * @uses \CommerceGuys\Tax\Resolver\Context::getCustomerTaxNumber
     * @uses \CommerceGuys\Tax\Resolver\Context::getStoreRegistrations
     * @uses \CommerceGuys\Tax\Resolver\Context::getDate
     */
    public function testConstructor()
    {
        $customerAddress = $this
            ->getMockBuilder('CommerceGuys\Addressing\Address')
            ->getMock();
        $storeAddress = $this
            ->getMockBuilder('CommerceGuys\Addressing\Address')
            ->getMock();
        $date = new \DateTime('2014-10-10');
        $context = new Context($customerAddress, $storeAddress, '0123', ['DE'], $date);
        $this->assertSame($customerAddress, $context->getCustomerAddress());
        $this->assertSame($storeAddress, $context->getStoreAddress());
        $this->assertEquals('0123', $context->getCustomerTaxNumber());
        $this->assertEquals(['DE'], $context->getStoreRegistrations());
        $this->assertSame($date, $context->getDate());
    }

    /**
     * @covers ::getCustomerAddress
     * @covers ::setCustomerAddress
     *
     * @uses \CommerceGuys\Tax\Resolver\Context::__construct
     */
    public function testCustomerAddress()
    {
        $address = $this
            ->getMockBuilder('CommerceGuys\Addressing\Address')
            ->getMock();
        $this->context->setCustomerAddress($address);
        $this->assertSame($address, $this->context->getCustomerAddress());
    }

    /**
     * @covers ::getStoreAddress
     * @covers ::setStoreAddress
     *
     * @uses \CommerceGuys\Tax\Resolver\Context::__construct
     */
    public function testStoreAddress()
    {
        $address = $this
            ->getMockBuilder('CommerceGuys\Addressing\Address')
            ->getMock();
        $this->context->setStoreAddress($address);
        $this->assertSame($address, $this->context->getStoreAddress());
    }

    /**
     * @covers ::getCustomerTaxNumber
     * @covers ::setCustomerTaxNumber
     *
     * @uses \CommerceGuys\Tax\Resolver\Context::__construct
     */
    public function testCustomerTaxNumber()
    {
        $this->context->setCustomerTaxNumber('123456');
        $this->assertEquals('123456', $this->context->getCustomerTaxNumber());
    }

    /**
     * @covers ::getStoreRegistrations
     * @covers ::setStoreRegistrations
     *
     * @uses \CommerceGuys\Tax\Resolver\Context::__construct
     */
    public function testStoreRegistrations()
    {
        $this->context->setStoreRegistrations(['DE', 'DK']);
        $this->assertEquals(['DE', 'DK'], $this->context->getStoreRegistrations());
    }

    /**
     * @covers ::getDate
     * @covers ::setDate
     *
     * @uses \CommerceGuys\Tax\Resolver\Context::__construct
     */
    public function testDate()
    {
        $date = new \DateTime('1990-02-24');
        $this->context->setDate($date);
        $this->assertSame($date, $this->context->getDate());
    }
}
