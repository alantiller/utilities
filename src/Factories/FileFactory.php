<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

use Exception;

class FileFactory
{
    private $filePath;
    private $fileHandle;
    
    public function __construct(string $filePath = null)
    {
        if ($filePath) {
            $this->filePath = $filePath;
        }
    }
    
    public function open(string $filePath = null, string $mode = 'r'): self
    {
        if ($filePath) {
            $this->filePath = $filePath;
        }
        
        if (!$this->filePath) {
            throw new Exception('No file path specified');
        }
        
        $this->fileHandle = fopen($this->filePath, $mode);
        if (!$this->fileHandle) {
            throw new Exception('Could not open file: ' . $this->filePath);
        }
        
        return $this;
    }
    
    public function read(int $length = null): string
    {
        if (!$this->fileHandle) {
            $this->open();
        }
        
        if ($length) {
            return fread($this->fileHandle, $length);
        } else {
            return stream_get_contents($this->fileHandle);
        }
    }
    
    public function write(string $data): self
    {
        if (!$this->fileHandle) {
            $this->open(null, 'w');
        }
        
        fwrite($this->fileHandle, $data);
        return $this;
    }
    
    public function close(): void
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }
    
    public function readCsv(bool $hasHeader = true): array
    {
        if (!$this->fileHandle) {
            $this->open();
        }
        
        $data = [];
        $headers = [];
        $rowCount = 0;
        
        while (($row = fgetcsv($this->fileHandle)) !== false) {
            if ($hasHeader && $rowCount === 0) {
                $headers = $row;
                $rowCount++;
                continue;
            }
            
            if ($hasHeader) {
                $associativeRow = [];
                foreach ($row as $key => $value) {
                    $associativeRow[$headers[$key]] = $value;
                }
                $data[] = $associativeRow;
            } else {
                $data[] = $row;
            }
            
            $rowCount++;
        }
        
        return $data;
    }
    
    public function writeCsv(array $data, bool $includeHeader = true): self
    {
        if (!$this->fileHandle) {
            $this->open(null, 'w');
        }
        
        // Write header if needed
        if ($includeHeader && !empty($data)) {
            $firstRow = reset($data);
            if (is_array($firstRow)) {
                fputcsv($this->fileHandle, array_keys($firstRow));
            }
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($this->fileHandle, $row);
        }
        
        return $this;
    }
    
    public function __destruct()
    {
        $this->close();
    }
}
