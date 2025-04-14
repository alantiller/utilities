<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

class PaginationFactory
{
    private $data = [];
    private $filteredData = [];
    private $searchTerm = null;
    private $searchColumns = [];
    private $offset = 0;
    private $limit = 10;
    private $totalItems = 0;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->filteredData = $data;
        $this->totalItems = count($data);
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        $this->filteredData = $data;
        $this->totalItems = count($data);
        return $this;
    }

    public function search(string $searchTerm, array $searchColumns = []): self
    {
        $this->searchTerm = $searchTerm;
        $this->searchColumns = $searchColumns;
        
        // Filter the data
        $this->filteredData = array_filter($this->data, function ($item) {
            foreach ($this->searchColumns as $column) {
                if (isset($item[$column]) && stripos($item[$column], $this->searchTerm) !== false) {
                    return true;
                }
            }
            return false;
        });
        
        return $this;
    }

    public function paginate(int $offset = 0, int $limit = 10): self
    {
        $this->offset = $offset;
        $this->limit = $limit;
        return $this;
    }

    public function getResult(): array
    {
        $result = [
            'total' => $this->totalItems,
            'filtered' => count($this->filteredData),
            'offset' => $this->offset,
            'limit' => $this->limit,
            'data' => array_slice($this->filteredData, $this->offset, $this->limit)
        ];
        
        if ($this->searchTerm) {
            $result['search'] = $this->searchTerm;
        }
        
        return $result;
    }
}
