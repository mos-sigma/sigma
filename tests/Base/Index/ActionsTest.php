<?php

declare(strict_types=1);

namespace Sigmie\Base\Tests;

use Sigmie\Base\Index\Actions as IndexActions;
use Sigmie\Base\Index\Index;
use Sigmie\Support\Contracts\Collection;
use Sigmie\Testing\ClearIndices;
use Sigmie\Testing\TestCase;
use Sigmie\Testing\TestConnection;

class ActionsTest extends TestCase
{
    use ClearIndices, IndexActions, TestConnection;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function index_exists()
    {
        $indexName = $this->testId() . '_foo';
        $index = new Index($indexName);

        $exists = $this->indexExists($index);

        $this->assertFalse($exists);

        $this->createIndex($index);

        $exists = $this->indexExists($index);
        $this->assertTrue($exists);
    }

    /**
     * @test
     */
    public function create_index(): void
    {
        $indexName = $this->testId() . '_bar';

        $this->createIndex(new Index($indexName));

        $this->assertIndexExists($indexName);
    }

    /**
     * @test
     */
    public function delete_index()
    {
        $indexName = $this->testId() . '_bar';
        $this->createIndex(new Index($indexName));

        $this->deleteIndex($indexName);

        $array = $this->listIndices()->map(fn (Index $index) => $index->getName())->toArray();

        $this->assertNotContains($indexName, $array);
    }

    /**
     * @test
     */
    public function list_indices()
    {
        $fooIndexName = $this->testId() . '_foo';
        $barIndexName = $this->testId() . '_bar';

        $this->createIndex(new Index($fooIndexName));
        $this->createIndex(new Index($barIndexName));

        $list = $this->listIndices();
        $array = $list->map(fn (Index $index) => $index->getName())->toArray();

        $this->assertContains($fooIndexName, $array);
        $this->assertContains($barIndexName, $array);

        $this->assertInstanceOf(Collection::class, $list);

        $list->forAll(fn ($key, $index) => $this->assertInstanceOf(Index::class, $index));
    }
}
