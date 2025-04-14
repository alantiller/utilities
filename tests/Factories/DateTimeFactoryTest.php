<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\DateTimeFactory;
use DateTime;
use DateTimeZone;

class DateTimeFactoryTest extends TestCase
{
    private DateTimeFactory $dateTimeFactory;
    
    protected function setUp(): void
    {
        $this->dateTimeFactory = new DateTimeFactory();
    }
    
    public function testConstructorWithNoArgument(): void
    {
        $factory = new DateTimeFactory();
        $this->assertInstanceOf(DateTime::class, $factory->getDateTime());
        
        // The DateTime should be close to now
        $now = new DateTime();
        $diff = $now->getTimestamp() - $factory->getDateTime()->getTimestamp();
        $this->assertLessThan(2, $diff); // Should be less than 2 seconds difference
    }
    
    public function testConstructorWithDatetimeString(): void
    {
        $dateString = '2023-05-15 14:30:00';
        $factory = new DateTimeFactory($dateString);
        
        $this->assertEquals($dateString, $factory->format('Y-m-d H:i:s'));
    }
    
    public function testFormat(): void
    {
        $dateString = '2023-05-15 14:30:00';
        $factory = new DateTimeFactory($dateString);
        
        $this->assertEquals('2023-05-15', $factory->format('Y-m-d'));
        $this->assertEquals('14:30:00', $factory->format('H:i:s'));
        $this->assertEquals('May 15, 2023', $factory->format('F j, Y'));
    }
    
    public function testTimeAgoJustNow(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $this->assertEquals('just now', $dateTimeFactory->timeAgo());
    }
    
    public function testTimeAgoMinutes(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $dateTimeFactory->modify('-10 minutes');
        $this->assertEquals('10 minutes ago', $dateTimeFactory->timeAgo());
    }
    
    public function testTimeAgoHours(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $dateTimeFactory->modify('-5 hours');
        $this->assertEquals('5 hours ago', $dateTimeFactory->timeAgo());
    }
    
    public function testTimeAgoDays(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $dateTimeFactory->modify('-3 days');
        $this->assertEquals('3 days ago', $dateTimeFactory->timeAgo());
    }
    
    public function testTimeAgoWeeks(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $dateTimeFactory->modify('-2 weeks');
        $this->assertEquals('2 weeks ago', $dateTimeFactory->timeAgo());
    }
    
    public function testTimeAgoMonths(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $dateTimeFactory->modify('-3 months');
        $this->assertEquals('3 months ago', $dateTimeFactory->timeAgo());
    }
    
    public function testTimeAgoYears(): void
    {
        $dateTimeFactory = new DateTimeFactory();
        $dateTimeFactory->modify('-2 years');
        $this->assertEquals('2 years ago', $dateTimeFactory->timeAgo());
    }
    
    public function testSetTimezone(): void
    {
        $dateTimeFactory = new DateTimeFactory('2023-05-15 14:30:00');
        $dateTimeFactory->setTimezone('America/New_York');
        
        $this->assertEquals('America/New_York', $dateTimeFactory->getDateTime()->getTimezone()->getName());
    }
    
    public function testModify(): void
    {
        $dateTimeFactory = new DateTimeFactory('2023-05-15 14:30:00');
        $dateTimeFactory->modify('+1 day');
        
        $this->assertEquals('2023-05-16 14:30:00', $dateTimeFactory->format());
    }
    
    public function testDiff(): void
    {
        $dateTimeFactory = new DateTimeFactory('2023-05-15 14:30:00');
        $diff = $dateTimeFactory->diff('2023-05-20 10:15:30');
        
        $this->assertEquals(4, $diff->d); // 4 days difference
        $this->assertEquals(19, $diff->h); // 19 hours difference
        $this->assertEquals(45, $diff->i); // 45 minutes difference
        $this->assertEquals(30, $diff->s); // 30 seconds difference
    }
    
    public function testGetDateTime(): void
    {
        $dateTimeFactory = new DateTimeFactory('2023-05-15 14:30:00');
        $dateTime = $dateTimeFactory->getDateTime();
        
        $this->assertInstanceOf(DateTime::class, $dateTime);
        $this->assertEquals('2023-05-15 14:30:00', $dateTime->format('Y-m-d H:i:s'));
    }
    
    public function testToString(): void
    {
        $dateTimeFactory = new DateTimeFactory('2023-05-15 14:30:00');
        
        $this->assertEquals('2023-05-15 14:30:00', $dateTimeFactory->toString());
        $this->assertEquals('2023-05-15', $dateTimeFactory->toString('Y-m-d'));
    }
    
    public function testMagicMethod(): void
    {
        $dateTimeFactory = new DateTimeFactory('2023-05-15 14:30:00');
        
        $this->assertEquals('2023-05-15 14:30:00', (string)$dateTimeFactory);
    }
}