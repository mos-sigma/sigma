<?php


declare(strict_types=1);

namespace Sigmie\Base\Shared;

use Closure;
use Iterator;
use Sigmie\Base\APIs\Count;
use Sigmie\Base\Documents\Actions;
use Sigmie\Base\Documents\Document;

trait LazyEach
{
    use Actions, Count;

    protected int $chunk = 500;

    public function chunk(int $size): self
    {
        $this->chunk = $size;

        return $this;
    }

    public function each(Closure $fn):self
    {
        foreach ($this->indexGenerator() as $key => $value) {
            $fn($value, $key);
        };

        return $this;
    }

    private function indexGenerator(): Iterator
    {
        $page = 1;
        $total = $this->countAPICall($this->name)->json('count');

        yield from $this->pageGenerator($page);


        while ($this->chunk * $page < $total) {

            $page++;

            yield from $this->pageGenerator($page);
        }
    }

    private function pageGenerator(int $page): Iterator
    {
        $body = [
            'from' => ($page - 1) * $this->chunk,
            'size' => $this->chunk,
            'query' => ['match_all' => (object) []]
        ];

        $response = $this->searchAPICall($this->name, $body);

        $values = $response->json('hits')['hits'];

        foreach ($values as $data) {
            yield $data['_id'] => new Document($data['_source'], $data['_id']);
        }
    }
}
