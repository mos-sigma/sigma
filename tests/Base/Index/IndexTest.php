<?php

declare(strict_types=1);

namespace Sigmie\Base\Tests\Index;

use ArrayAccess;
use Countable;
use Exception;
use IteratorAggregate;
use Sigmie\Base\Contracts\DocumentCollection as DocumentCollectionInterface;
use Sigmie\Base\Documents\Document;
use Sigmie\Base\Documents\DocumentsCollection;
use Sigmie\Testing\TestCase;
use Sigmie\Testing\TestIndex;

class IndexTest extends TestCase
{
    use TestIndex;

    /**
     * @test
     */
    public function mass_delete_docs()
    {
        $index = $this->getTestIndex();

        $docs = [new Document(['bar' => 'foo'], '1'), new Document(['foo' => 'bar'], '2')];

        $index->addDocuments($docs);

        $this->assertCount(2, $index);

        $index->remove(['1', '2']);

        $this->assertCount(0, $index);
    }

    /**
     * @test
     */
    public function add_or_update()
    {
        $index = $this->getTestIndex();

        $document = new Document(['foo' => 'bar'], 'id');

        $index->addDocument($document);

        $document->setAttribute('foo', 'john');

        $doc = $index['id'];

        $this->assertEquals($doc->getAttribute('foo'), 'john');
    }

    /**
     * @test
     */
    public function offset_unset()
    {
        $index = $this->getTestIndex();

        $index->addAsyncDocuments([new Document(['foo' => 'bar'], '4'),]);

        $this->assertCount(1, $index);

        unset($index['4']);

        $this->assertCount(0, $index);
    }

    /**
     * @test
     */
    public function offset_exists()
    {
        $index = $this->getTestIndex();

        $index->addAsyncDocuments([new Document(['foo' => 'bar'], '4'),]);

        $this->assertTrue($index->offsetExists('4'));
        $this->assertFalse($index->offsetExists('6'));
    }

    /**
     * @test
     */
    public function offset_set_with_offset()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar'], '4'),
        ];
        $index->addAsyncDocuments($docs);

        $doc = new Document(['foo' => 'baz'], '89');

        $this->expectException(Exception::class);

        $index->offsetSet('34', $doc);
    }

    /**
     * @test
     */
    public function offset_set()
    {
        $index = $this->getTestIndex();

        $index->addAsyncDocuments([new Document(['foo' => 'bar'], '4'),]);

        $doc = new Document(['foo' => 'baz'], '89');

        $index->offsetSet(null, $doc);

        $this->assertCount(2, $index);
        $this->assertNotNull($index['89']);
        $this->assertNull($index['10']);
    }

    /**
     * @test
     */
    public function offset_get()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar'], '4'),
            new Document(['foo' => 'baz'], '89'),
            new Document(['baz' => 'john'], '2'),
        ];

        $index->addDocuments($docs);

        $doc = $index->offsetGet('4');

        $this->assertEquals('bar', $doc->getAttribute('foo'));
        $this->assertEquals('4', $doc->getId());
    }

    /**
     * @test
     */
    public function remove_document()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar'], '4'),
            new Document(['foo' => 'bar'], '89'),
            new Document(['baz' => 'john'], '2'),
        ];

        $index->addDocuments($docs);

        $this->assertCount(3, $index);

        $index->remove('89');

        $this->assertCount(2, $index);
    }

    /**
     * @test
     */
    public function last_and_first()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar'], '4'),
            new Document(['foo' => 'bar'], '5'),
            new Document(['foo' => 'bar'], '8'),
            new Document(['foo' => 'bar'], '9'),
            new Document(['foo' => 'bar'], '0'),
            new Document(['foo' => 'bar'], '89'),
            new Document(['baz' => 'john'], '2'),
        ];

        $index->addDocuments($docs);

        $this->assertEquals('4', $index->first()->getId());
        $this->assertEquals('2', $index->last()->getId());
    }

    public function index_to_array()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar']),
            new Document(['baz' => 'john'], '2'),
            new Document(['baz' => 'john'], '3'),
        ];

        $index->addAsyncDocuments($docs);

        $array = $index->toArray();

        $this->assertIsArray($array);
        $this->assertCount(3, $array);
    }

    /**
     * @test
     */
    public function index_clear_and_is_empty()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar']),
            new Document(['baz' => 'john'], '2'),
        ];

        $index->addAsyncDocuments($docs);

        $index->clear();

        $this->assertCount(0, $index);
        $this->assertTrue($index->isEmpty());
        $this->assertFalse($index->isNotEmpty());
    }

    /**
     * @test
     */
    public function add_documents_accepts_collection_or_array()
    {
        $index = $this->getTestIndex();

        $docs = [
            new Document(['foo' => 'bar']),
            new Document(['baz' => 'john'], '2'),
        ];

        $index->addAsyncDocuments($docs);

        $docs = [
            new Document(['foo' => 'bar']),
            new Document(['baz' => 'john'], '3'),
        ];

        $index->addDocuments($docs);

        $this->assertCount(4, $index);
    }

    /**
     * @test
     */
    public function add_document_assigns_id()
    {
        $index = $this->getTestIndex();

        $doc = new Document(['foo' => 'bar']);

        $this->assertNull($doc->getId());

        $index->addAsyncDocument($doc);

        $this->assertNotNull($doc->getId());
    }

    /**
     * @test
     */
    public function index_collection_keys()
    {
        $index = $this->getTestIndex();

        $docs = new DocumentsCollection([
            new Document(['foo' => 'bar']),
            new Document(['baz' => 'john'], '2'),
        ]);

        $this->assertNull($docs->first()->getId());

        $index->addAsyncDocuments($docs->toArray());

        $this->assertNotNull($docs->first()->getId());
        $this->assertEquals(2, $docs->last()->getId());
    }

    /**
     * @test
     */
    public function index_collection_methods()
    {
        $index = $this->getTestIndex();

        $docs = new DocumentsCollection([
            new Document(['foo' => 'bar']),
            new Document(['baz' => 'john']),
            new Document(['baz' => 'john']),
        ]);

        $this->assertNull($docs->first()->getId());

        $index->addAsyncDocuments($docs->toArray());

        $this->assertNotNull($docs->first()->getId());
    }

    /**
     * @test
     */
    public function index_interfaces()
    {
        $index = $this->getTestIndex();

        $this->assertInstanceOf(DocumentCollectionInterface::class, $index);
        $this->assertInstanceOf(Countable::class, $index);
        $this->assertInstanceOf(ArrayAccess::class, $index);
        $this->assertInstanceOf(IteratorAggregate::class, $index);
    }
}
