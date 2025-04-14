<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\PaginationFactory;

class PaginationFactoryTest extends TestCase
{
    private $sampleData;
    private PaginationFactory $paginationFactory;
    
    protected function setUp(): void
    {
        $this->sampleData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com'],
            ['id' => 4, 'name' => 'Alice Brown', 'email' => 'alice@example.com'],
            ['id' => 5, 'name' => 'Charlie Davis', 'email' => 'charlie@example.com'],
            ['id' => 6, 'name' => 'Eva White', 'email' => 'eva@example.com'],
            ['id' => 7, 'name' => 'David Black', 'email' => 'david@example.com'],
            ['id' => 8, 'name' => 'Frank Miller', 'email' => 'frank@example.com'],
            ['id' => 9, 'name' => 'Grace Lee', 'email' => 'grace@example.com'],
            ['id' => 10, 'name' => 'Henry Wilson', 'email' => 'henry@example.com'],
            ['id' => 11, 'name' => 'Irene Taylor', 'email' => 'irene@example.com'],
            ['id' => 12, 'name' => 'Jack Robinson', 'email' => 'jack@example.com']
        ];
        
        $this->paginationFactory = new PaginationFactory($this->sampleData);
    }
    
    public function testConstructor(): void
    {
        $result = $this->paginationFactory->getResult();
        
        $this->assertEquals(12, $result['total']);
        $this->assertEquals(12, $result['filtered']);
        $this->assertEquals(0, $result['offset']);
        $this->assertEquals(10, $result['limit']);
        $this->assertCount(10, $result['data']); // Default limit is 10
    }
    
    public function testSetData(): void
    {
        $newData = [
            ['id' => 1, 'name' => 'Test User'],
            ['id' => 2, 'name' => 'Another User']
        ];
        
        $result = $this->paginationFactory->setData($newData)->getResult();
        
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['filtered']);
        $this->assertCount(2, $result['data']);
    }
    
    public function testSearch(): void
    {
        $result = $this->paginationFactory
            ->search('john', ['name', 'email'])
            ->getResult();
        
        $this->assertEquals(12, $result['total']); // Total count remains the same
        $this->assertEquals(2, $result['filtered']); // Only one item matches
        $this->assertCount(2, $result['data']);
        $this->assertEquals('John Doe', $result['data'][0]['name']);
        $this->assertEquals('john', $result['search']);
    }
    
    public function testSearchMultipleMatches(): void
    {
        $result = $this->paginationFactory
            ->search('e', ['email']) // This will match multiple emails
            ->getResult();
        
        $this->assertEquals(12, $result['total']);
        $this->assertEquals(12, $result['filtered']); // All emails contain 'e'
        $this->assertCount(10, $result['data']); // Default limit is 10
    }
    
    public function testSearchNoMatches(): void
    {
        $result = $this->paginationFactory
            ->search('nonexistent', ['name', 'email'])
            ->getResult();
        
        $this->assertEquals(12, $result['total']);
        $this->assertEquals(0, $result['filtered']);
        $this->assertCount(0, $result['data']);
    }
    
    public function testPaginate(): void
    {
        $result = $this->paginationFactory
            ->paginate(2, 3)
            ->getResult();
        
        $this->assertEquals(12, $result['total']);
        $this->assertEquals(12, $result['filtered']);
        $this->assertEquals(2, $result['offset']);
        $this->assertEquals(3, $result['limit']);
        $this->assertCount(3, $result['data']);
        
        // Check if we got the right items (items 3, 4, 5)
        $this->assertEquals(3, $result['data'][0]['id']);
        $this->assertEquals(4, $result['data'][1]['id']);
        $this->assertEquals(5, $result['data'][2]['id']);
    }
    
    public function testPaginateWithSearch(): void
    {
        $result = $this->paginationFactory
            ->search('a', ['name']) // Will match names containing 'a'
            ->paginate(1, 2)
            ->getResult();
        
        // Names with 'a': Jane, David, Frank, Grace, Taylor, Jack
        $this->assertEquals(12, $result['total']);
        $this->assertGreaterThan(1, $result['filtered']);
        $this->assertEquals(1, $result['offset']);
        $this->assertEquals(2, $result['limit']);
        $this->assertCount(2, $result['data']);
    }
    
    public function testPaginateBeyondAvailableData(): void
    {
        $result = $this->paginationFactory
            ->paginate(15, 5) // Offset beyond available data
            ->getResult();
        
        $this->assertEquals(12, $result['total']);
        $this->assertEquals(12, $result['filtered']);
        $this->assertEquals(15, $result['offset']);
        $this->assertEquals(5, $result['limit']);
        $this->assertCount(0, $result['data']); // No data returned
    }
}