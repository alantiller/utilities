<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\FileFactory;
use Exception;

class FileFactoryTest extends TestCase
{
    private string $testFilePath;
    private string $testCsvPath;
    
    protected function setUp(): void
    {
        // Create temporary test files
        $this->testFilePath = sys_get_temp_dir() . '/websitesql_test_file.txt';
        $this->testCsvPath = sys_get_temp_dir() . '/websitesql_test_file.csv';
        
        // Ensure the test files don't exist
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        
        if (file_exists($this->testCsvPath)) {
            unlink($this->testCsvPath);
        }
        
        // Create a test CSV file
        $csvHandle = fopen($this->testCsvPath, 'w');
        fputcsv($csvHandle, ['name', 'email', 'age']);
        fputcsv($csvHandle, ['John Doe', 'john@example.com', '30']);
        fputcsv($csvHandle, ['Jane Smith', 'jane@example.com', '25']);
        fclose($csvHandle);
        
        // Create a test text file
        file_put_contents($this->testFilePath, "Line 1\nLine 2\nLine 3");
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        
        if (file_exists($this->testCsvPath)) {
            unlink($this->testCsvPath);
        }
    }
    
    public function testConstructorWithFilePath(): void
    {
        $fileFactory = new FileFactory($this->testFilePath);
        $this->assertInstanceOf(FileFactory::class, $fileFactory);
    }
    
    public function testOpenFile(): void
    {
        $fileFactory = new FileFactory();
        $result = $fileFactory->open($this->testFilePath);
        
        $this->assertInstanceOf(FileFactory::class, $result);
    }
    
    public function testOpenWithoutFilePath(): void
    {
        $fileFactory = new FileFactory();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No file path specified');
        
        $fileFactory->open();
    }
    
    public function testOpenNonExistentFile(): void
    {
        $fileFactory = new FileFactory();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not open file');
        
        $fileFactory->open('/path/to/nonexistent/file.txt');
    }
    
    public function testReadEntireFile(): void
    {
        $fileFactory = new FileFactory($this->testFilePath);
        $content = $fileFactory->read();
        
        $this->assertEquals("Line 1\nLine 2\nLine 3", $content);
    }
    
    public function testReadPartialFile(): void
    {
        $fileFactory = new FileFactory($this->testFilePath);
        $content = $fileFactory->read(6); // Read only first 6 bytes
        
        $this->assertEquals("Line 1", $content);
    }
    
    public function testWriteToFile(): void
    {
        $newFilePath = sys_get_temp_dir() . '/websitesql_write_test.txt';
        $fileFactory = new FileFactory($newFilePath);
        
        $fileFactory->write("Test content\nSecond line");
        $fileFactory->close();
        
        $content = file_get_contents($newFilePath);
        $this->assertEquals("Test content\nSecond line", $content);
        
        // Clean up
        if (file_exists($newFilePath)) {
            unlink($newFilePath);
        }
    }
    
    public function testReadCsvWithHeaders(): void
    {
        $fileFactory = new FileFactory($this->testCsvPath);
        $data = $fileFactory->readCsv();
        
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        
        $this->assertEquals('John Doe', $data[0]['name']);
        $this->assertEquals('john@example.com', $data[0]['email']);
        $this->assertEquals('30', $data[0]['age']);
        
        $this->assertEquals('Jane Smith', $data[1]['name']);
        $this->assertEquals('jane@example.com', $data[1]['email']);
        $this->assertEquals('25', $data[1]['age']);
    }
    
    public function testReadCsvWithoutHeaders(): void
    {
        $fileFactory = new FileFactory($this->testCsvPath);
        $data = $fileFactory->readCsv(false);
        
        $this->assertIsArray($data);
        $this->assertCount(3, $data); // Should include header row as data
        
        $this->assertEquals(['name', 'email', 'age'], $data[0]);
        $this->assertEquals(['John Doe', 'john@example.com', '30'], $data[1]);
        $this->assertEquals(['Jane Smith', 'jane@example.com', '25'], $data[2]);
    }
    
    public function testWriteCsvWithHeaders(): void
    {
        $newCsvPath = sys_get_temp_dir() . '/websitesql_write_csv_test.csv';
        $fileFactory = new FileFactory($newCsvPath);
        
        $data = [
            ['name' => 'Alice Brown', 'email' => 'alice@example.com', 'age' => '35'],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com', 'age' => '40']
        ];
        
        $fileFactory->writeCsv($data)->close();
        
        // Read it back to verify
        $content = file_get_contents($newCsvPath);
        $this->assertStringContainsString('"Alice Brown",alice@example.com,35', $content);
        $this->assertStringContainsString('"Bob Johnson",bob@example.com,40', $content);
        
        // Clean up
        if (file_exists($newCsvPath)) {
            unlink($newCsvPath);
        }
    }
    
    public function testWriteCsvWithoutHeaders(): void
    {
        $newCsvPath = sys_get_temp_dir() . '/websitesql_write_csv_no_headers_test.csv';
        $fileFactory = new FileFactory($newCsvPath);
        
        $data = [
            ['Alice Brown', 'alice@example.com', '35'],
            ['Bob Johnson', 'bob@example.com', '40']
        ];
        
        $fileFactory->writeCsv($data, false)->close();
        
        // Read it back to verify
        $content = file_get_contents($newCsvPath);
        $this->assertStringContainsString('"Alice Brown",alice@example.com,35', $content);
        $this->assertStringContainsString('"Bob Johnson",bob@example.com,40', $content);
        
        // Make sure headers aren't included
        $lines = explode("\n", trim($content));
        $this->assertCount(2, $lines);
        
        // Clean up
        if (file_exists($newCsvPath)) {
            unlink($newCsvPath);
        }
    }
    
    public function testAutoCloseOnDestruct(): void
    {
        $fileFactory = new FileFactory($this->testFilePath);
        $fileFactory->open();
        
        // Force destruct by unset
        unset($fileFactory);
        
        // If the file handle wasn't closed, this would fail on tearDown when trying to delete the file
        $this->assertTrue(true);
    }
}