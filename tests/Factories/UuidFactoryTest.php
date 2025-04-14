<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\UuidFactory;
use Exception;

class UuidFactoryTest extends TestCase
{
    private UuidFactory $uuidFactory;

    protected function setUp(): void
    {
        $this->uuidFactory = new UuidFactory();
    }

    public function testGenerateCreatesValidUuid(): void
    {
        $uuid = $this->uuidFactory->generate()->toString();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testGenerateVersionFour(): void
    {
        $uuid = $this->uuidFactory->generate('4')->toString();
        // Version 4 UUID has the third segment start with '4'
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testGenerateVersionOne(): void
    {
        $uuid = $this->uuidFactory->generate('1')->toString();
        // Version 1 UUID has the third segment start with '1'
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-1[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testGenerateVersionThree(): void
    {
        $uuid = $this->uuidFactory->generate('3')->toString();
        // Version 3 UUID has the third segment start with '3'
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testGenerateVersionFive(): void
    {
        $uuid = $this->uuidFactory->generate('5')->toString();
        // Version 5 UUID has the third segment start with '5'
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testGenerateUnsupportedVersion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unsupported UUID version: 6");
        $this->uuidFactory->generate('6');
    }

    public function testToStringReturnsUuid(): void
    {
        $uuid = $this->uuidFactory->generate()->toString();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }

    public function testMagicMethodReturnsUuid(): void
    {
        $uuidString = (string)$this->uuidFactory->generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuidString);
    }

    public function testFromStringWithValidUuid(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = $this->uuidFactory->fromString($validUuid)->toString();
        $this->assertSame($validUuid, $result);
    }

    public function testFromStringWithInvalidUuid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid UUID format");
        $this->uuidFactory->fromString('not-a-valid-uuid');
    }

    public function testGetVersion(): void
    {
        $this->uuidFactory->generate('4');
        $this->assertSame(4, $this->uuidFactory->getVersion());
        
        $this->uuidFactory->generate('1');
        $this->assertSame(1, $this->uuidFactory->getVersion());
    }
}