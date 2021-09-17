<?php

declare(strict_types=1);

namespace Sigmie\Tests\Base\Index;

use Carbon\Carbon;
use Sigmie\Base\Analysis\Analyzer;
use Sigmie\Base\Analysis\CharFilter\HTMLStrip;
use Sigmie\Base\Analysis\CharFilter\Pattern as PatternCharFilter;
use Sigmie\Base\Analysis\TokenFilter\Stopwords;
use Sigmie\Base\Analysis\Tokenizers\Whitespace;
use Sigmie\Base\APIs\Index;
use Sigmie\Base\Documents\Document;
use Sigmie\Base\Documents\DocumentsCollection;
use Sigmie\Base\Index\Blueprint;
use function Sigmie\Helpers\name_configs;
use Sigmie\Support\Alias\Actions;
use Sigmie\Support\Index\AliasedIndex;
use Sigmie\Support\Update\Update as Update;
use Sigmie\Testing\Assert;
use Sigmie\Testing\TestCase;
use TypeError;

class UpdateTest extends TestCase
{
    use Index, Actions;

    /**
     * @test
     */
    public function analyzer_remove_html_char_filters()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->stopwords(['foo', 'bar'], 'demo')
            ->mapChars(['foo' => 'bar'], 'some_char_filter_name')
            ->stripHTML()
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasFilter('default', 'demo');
            $index->assertFilterHasStopwords('demo', ['foo', 'bar']);
            $index->assertAnalyzerHasCharFilter('default', 'html_strip');
            $index->assertAnalyzerHasCharFilter('default', 'some_char_filter_name');
        });


        $this->sigmie->index($alias)->update(function (Update $update) {

            $update->withoutMappings()
                ->stopwords(['foo', 'bar'], 'demo');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasNotCharFilter('default', 'html_strip');
            $index->assertAnalyzerHasNotCharFilter('default', 'some_char_filter_name');
        });
    }

    /**
     * @test
     */
    public function update_char_filter()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->mapChars(['bar' => 'baz'], 'map_chars_char_filter')
            ->patternReplace('/bar/', 'foo', 'pattern_replace_char_filter')
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasCharFilter('default', 'map_chars_char_filter');
            $index->assertCharFilterEquals('map_chars_char_filter', [
                'type' => 'mapping',
                'mappings' => ['bar => baz']
            ]);

            $index->assertAnalyzerHasCharFilter('default', 'pattern_replace_char_filter');
            $index->assertCharFilterEquals('pattern_replace_char_filter', [
                'type' => 'pattern_replace',
                'pattern' => '/bar/',
                'replacement' => 'foo'
            ]);
        });


        $this->sigmie->index($alias)->update(function (Update $update) {

            $update->withoutMappings();
            $update->mapChars(['baz' => 'foo'], 'map_chars_char_filter');
            $update->patternReplace('/doe/', 'john', 'pattern_replace_char_filter');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasCharFilter('default', 'map_chars_char_filter');
            $index->assertCharFilterEquals('map_chars_char_filter', [
                'type' => 'mapping',
                'mappings' => ['baz => foo']
            ]);

            $index->assertAnalyzerHasCharFilter('default', 'pattern_replace_char_filter');
            $index->assertCharFilterEquals('pattern_replace_char_filter', [
                'type' => 'pattern_replace',
                'pattern' => '/doe/',
                'replacement' => 'john'
            ]);
        });
    }

    /**
     * @test
     */
    public function default_char_filter()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerCharFilterIsEmpty('default');
        });

        $this->sigmie->index($alias)->update(function (Update $update) {

            $update->withoutMappings();
            $update->patternReplace('/foo/', 'bar', 'default_pattern_replace_filter');
            $update->mapChars(['foo' => 'bar'], 'default_mappings_filter');
            $update->stripHTML();

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasCharFilter('default', 'default_pattern_replace_filter');
            $index->assertAnalyzerHasCharFilter('default', 'default_mappings_filter');
            $index->assertAnalyzerHasCharFilter('default', 'html_strip');
        });
    }

    /**
     * @test
     */
    public function default_tokenizer_configurable()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerCharFilterIsEmpty('default');
            $index->assertAnalyzerHasTokenizer('default', 'standard');
        });

        $this->sigmie->index($alias)->update(function (Update $update) {

            $update->withoutMappings();
            $update->tokenizeOn()->pattern('/foo/', name: 'default_analyzer_pattern_tokenizer');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasTokenizer('default', 'default_analyzer_pattern_tokenizer');
            $index->assertTokenizerEquals('default_analyzer_pattern_tokenizer', [
                'pattern' => '/foo/',
                'type' => 'pattern',
            ]);
        });
    }

    /**
     * @test
     */
    public function default_tokenizer()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->setTokenizer(new Whitespace)
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerTokenizerIsWhitespaces('default');
        });

        $this->sigmie->index($alias)->update(function (Update $update) {
            $update->withoutMappings();

            $update->tokenizeOn()->wordBoundaries('foo_tokenizer');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertAnalyzerHasTokenizer('default', 'foo_tokenizer');
        });
    }

    /**
     * @test
     */
    public function update_index_one_way_synonyms()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->oneWaySynonyms([
                ['ipod', ['i-pod', 'i pod']]
            ], 'bar_name')
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('bar_name');
            $index->assertFilterHasSynonyms('bar_name', [
                'i-pod, i pod => ipod',
            ]);
        });

        $this->sigmie->index($alias)->update(function (Update $update) {
            $update->withoutMappings();

            $update->oneWaySynonyms([
                ['mickey', ['mouse', 'goofy']]
            ], 'bar_name');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('bar_name');
            $index->assertFilterHasSynonyms('bar_name', [
                'mouse, goofy => mickey',
            ]);
        });
    }

    /**
     * @test
     */
    public function update_index_stemming()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->stemming([
                ['am', ['be', 'are']],
                ['mouse', ['mice']],
                ['feet', ['foot']],
            ], 'bar_name')
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('bar_name');
            $index->assertFilterHasStemming('bar_name', [
                'be, are => am',
                'mice => mouse',
                'foot => feet',
            ]);
        });


        $this->sigmie->index($alias)->update(function (Update $update) {

            $update->withoutMappings();

            $update->stemming([[
                'mickey', ['mouse', 'goofy'],
            ]], 'bar_name');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('bar_name');
            $index->assertFilterHasStemming('bar_name', [
                'mouse, goofy => mickey',
            ]);
        });
    }

    /**
     * @test
     */
    public function update_index_synonyms()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->twoWaySynonyms([
                ['treasure', 'gem', 'gold', 'price'],
                ['friend', 'buddy', 'partner']
            ], 'foo_two_way_synonyms',)
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('foo_two_way_synonyms');
            $index->assertFilterHasSynonyms('foo_two_way_synonyms', [
                'treasure, gem, gold, price',
                'friend, buddy, partner'
            ]);
        });


        $this->sigmie->index($alias)->update(function (Update $update) {

            $update->withoutMappings();

            $update->twoWaySynonyms([['john', 'doe']], 'foo_two_way_synonyms');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterHasSynonyms('foo_two_way_synonyms', [
                'john, doe',
            ]);
        });
    }

    /**
     * @test
     */
    public function update_index_stopwords()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->stopwords(['foo', 'bar', 'baz'], 'foo_stopwords',)
            ->create();

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('foo_stopwords');
            $index->assertFilterHasStopwords('foo_stopwords', ['foo', 'bar', 'baz']);
        });

        $this->sigmie->index($alias)->update(function (Update $update) {
            $update->withoutMappings();

            $update->stopwords(['john', 'doe'], 'foo_stopwords');

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertFilterExists('foo_stopwords');
            $index->assertFilterHasStopwords('foo_stopwords', ['john', 'doe']);
        });
    }

    /**
     * @test
     */
    public function exception_when_not_returned()
    {
        $alias = uniqid();

        $this->expectException(TypeError::class);

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->stopwords(['foo', 'bar', 'baz'], 'foo_stopwords',)
            ->create();

        $this->sigmie->index($alias)->update(function (Update $update) {
        });
    }

    /**
     * @test
     */
    public function mappings()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->mapping(function (Blueprint $blueprint) {

                $blueprint->text('bar')->searchAsYouType();
                $blueprint->text('created_at')->unstructuredText();

                return $blueprint;
            })
            ->create();

        $index = $this->sigmie->index($alias);

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertPropertyIsUnstructuredText('created_at');
        });

        $index->update(function (Update $update) {

            $update->mapping(function (Blueprint $blueprint) {

                $blueprint->date('created_at');
                $blueprint->number('count')->float();

                return $blueprint;
            });

            return $update;
        });

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertPropertyExists('count');
            $index->assertPropertyExists('created_at');
            $index->assertPropertyIsDate('created_at');
        });
    }

    /**
     * @test
     */
    public function reindex_docs()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->create();

        $index = $this->sigmie->index($alias);
        $oldIndexName = $index->name();

        $docs = new DocumentsCollection();
        for ($i = 0; $i < 10; $i++) {
            $docs->addDocument(new Document(['foo' => 'bar']));
        }

        $index->addDocuments($docs);

        $this->assertCount(10, $index);

        $updatedIndex = $index->update(function (Update $update) {
            $update->withoutMappings();
            $update->replicas(3);
            return $update;
        });

        $config = $updatedIndex->toRaw();

        $this->assertEquals(3, $config['settings']['number_of_replicas']);
        $this->assertNotEquals($oldIndexName, $updatedIndex->name());
        $this->assertCount(10, $updatedIndex);
    }

    /**
     * @test
     */
    public function delete_old_index()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->create();

        $index = $this->sigmie->index($alias);

        $oldIndexName = $index->name();

        $index = $index->update(function (Update $update) {
            $update->withoutMappings();

            return $update;
        });

        $this->assertIndexNotExists($oldIndexName);
        $this->assertNotEquals($oldIndexName, $index->name());
    }

    /**
     * @test
     */
    public function index_name()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->create();

        $index = $this->sigmie->index($alias);

        $oldIndexName = $index->name();

        $index = $index->update(function (Update $update) {

            $update->withoutMappings();

            return $update;
        });

        $this->assertIndexExists($index->name());
        $this->assertIndexNotExists($oldIndexName);
        $this->assertNotEquals($oldIndexName, $index->name());
    }

    /**
     * @test
     */
    public function change_index_alias()
    {
        $oldAlias = uniqid();
        $newAlias = uniqid();

        $this->sigmie->newIndex($oldAlias)
            ->withoutMappings()
            ->create();

        $index = $this->sigmie->index($oldAlias);

        $this->assertInstanceOf(AliasedIndex::class, $index);

        $index->update(function (Update $update) use ($newAlias) {

            $update->withoutMappings();

            $update->alias($newAlias);

            return $update;
        });

        $index = $this->sigmie->index($newAlias);

        $oldIndex = $this->sigmie->index($oldAlias);

        $this->assertNull($oldIndex);
    }

    /**
     * @test
     */
    public function index_shards_and_replicas()
    {
        $alias = uniqid();

        $this->sigmie->newIndex($alias)
            ->withoutMappings()
            ->shards(1)
            ->replicas(1)
            ->create();

        $index = $this->sigmie->index($alias);

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertShards(1);
            $index->assertReplicas(1);
        });

        $index->update(function (Update $update) {

            $update->withoutMappings();

            $update->replicas(2)->shards(2);

            return $update;
        });

        $index = $this->sigmie->index($alias);

        $this->assertIndex($alias, function (Assert $index) {
            $index->assertShards(2);
            $index->assertReplicas(2);
        });
    }
}
