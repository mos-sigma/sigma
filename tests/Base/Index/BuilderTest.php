<?php

declare(strict_types=1);

namespace Sigmie\Tests\Base\Index;

use RachidLaasri\Travel\Travel;
use Sigmie\Base\Analysis\Analyzer;
use Sigmie\Base\Analysis\CharFilter\HTMLFilter;
use Sigmie\Base\Analysis\CharFilter\MappingFilter;
use Sigmie\Base\Analysis\CharFilter\PatternFilter;
use Sigmie\Base\Analysis\Languages\English;
use Sigmie\Base\Analysis\Languages\English\PossessiveStemmer;
use Sigmie\Base\Analysis\Languages\English\Stemmer as EnglishStemmer;
use Sigmie\Base\Analysis\Languages\English\Stopwords as EnglishStopwords;
use Sigmie\Base\Analysis\Languages\German;
use Sigmie\Base\Analysis\Languages\German\Stemmer as GermanStemmer;
use Sigmie\Base\Analysis\Languages\German\Stopwords as GermanStopwords;
use Sigmie\Base\Analysis\Languages\Greek;
use Sigmie\Base\Analysis\Languages\Greek\Lowercase;
use Sigmie\Base\Analysis\Languages\Greek\Stemmer as GreekStemmer;
use Sigmie\Base\Analysis\Languages\Greek\Stopwords as GreekStopwords;
use Sigmie\Base\Analysis\TokenFilter\OneWaySynonyms;
use Sigmie\Base\Analysis\TokenFilter\Stemmer;
use Sigmie\Base\Analysis\TokenFilter\Stopwords;
use Sigmie\Base\Analysis\TokenFilter\TwoWaySynonyms;
use Sigmie\Base\Analysis\Tokenizers\NonLetter;
use Sigmie\Base\Analysis\Tokenizers\Pattern;
use Sigmie\Base\Analysis\Tokenizers\Whitespaces;
use Sigmie\Base\Analysis\Tokenizers\WordBoundaries;
use Sigmie\Base\APIs\Calls\Index;
use Sigmie\Base\Exceptions\MissingMapping;
use Sigmie\Base\Index\AliasActions;
use Sigmie\Base\Index\Blueprint;
use Sigmie\Base\Index\Builder as NewIndex;
use Sigmie\Base\Index\Settings;
use Sigmie\Base\Mappings\Properties;
use Sigmie\Sigmie;
use Sigmie\Testing\ClearIndices;
use Sigmie\Testing\TestCase;

class BuilderTest extends TestCase
{
    use Index, ClearIndices, AliasActions;

    /**
     * @var Sigmie
     */
    private $sigmie;

    public function setUp(): void
    {
        parent::setUp();

        $this->sigmie = new Sigmie($this->httpConnection, $this->events);
    }

    /**
     * @test
     */
    public function pattern_tokenizer()
    {
        $this->sigmie->newIndex('sigmie')
            ->tokenizeOn(new Pattern('/[ ]/'))
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertEquals('sigmie_tokenizer', $data['settings']['index']['analysis']['analyzer']['sigmie_analyzer']['tokenizer']);
        $this->assertArrayHasKey('sigmie_tokenizer', $data['settings']['index']['analysis']['tokenizer']);
        $this->assertEquals([
            'type' => 'pattern',
            'pattern' => '/[ ]/',
            'class' => Pattern::class
        ], $data['settings']['index']['analysis']['tokenizer']['sigmie_tokenizer']);
    }

    /**
     * @test
     */
    public function non_letter_tokenizer()
    {
        $this->sigmie->newIndex('sigmie')
            ->tokenizeOn(new NonLetter())
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertEquals('letter', $data['settings']['index']['analysis']['analyzer']['sigmie_analyzer']['tokenizer']);
    }

    /**
     * @test
     */
    public function mapping_char_filters()
    {
        $this->sigmie->newIndex('sigmie')
            ->normalizer(new MappingFilter(['a' => 'bar', 'f' => 'foo']))
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('sigmie_mapping_char_filter', $data['settings']['index']['analysis']['char_filter']);
        $this->assertEquals([
            'type' => 'mapping',
            'mappings' => ['a => bar', 'f => foo']
        ], $data['settings']['index']['analysis']['char_filter']['sigmie_mapping_char_filter']);
    }

    /**
     * @test
     */
    public function pattern_char_filters()
    {
        $this->sigmie->newIndex('sigmie')
            ->normalizer(new PatternFilter('/foo/', '$1'))
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('pattern_char_filter', $data['settings']['index']['analysis']['char_filter']);
        $this->assertEquals([
            'pattern' => '/foo/',
            'type' => 'pattern_replace',
            'replacement' => '$1',
            'class' => PatternFilter::class
        ], $data['settings']['index']['analysis']['char_filter']['pattern_char_filter']);
    }

    /**
     * @test
     */
    public function html_char_filters()
    {
        $this->sigmie->newIndex('sigmie')
            ->normalizer(new HTMLFilter)
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertContains('html_strip', $data['settings']['index']['analysis']['analyzer']['default']['char_filter']);
    }

    /**
     * @test
     */
    public function word_boundaries_tokenizer()
    {
        $this->sigmie->newIndex('sigmie')
            ->tokenizeOn(new WordBoundaries(40))
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertEquals('sigmie_tokenizer', $data['settings']['index']['analysis']['analyzer']['default']['tokenizer']);
        $this->assertArrayHasKey('sigmie_tokenizer', $data['settings']['index']['analysis']['tokenizer']);
        $this->assertEquals([
            'type' => 'standard',
            'max_token_length' => 40,
            'class' => WordBoundaries::class
        ], $data['settings']['index']['analysis']['tokenizer']['sigmie_tokenizer']);
    }

    /**
     * @test
     */
    public function whitespace_tokenizer()
    {
        $this->sigmie->newIndex('sigmie')
            ->tokenizeOn(new Whitespaces)
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertEquals('whitespace', $data['settings']['index']['analysis']['analyzer']['default']['tokenizer']);
    }

    /**
     * @test
     */
    public function mapping_exception()
    {
        $this->expectException(MissingMapping::class);

        $this->sigmie->newIndex('sigmie')
            ->create();
    }

    /**
     * @test
     */
    public function german_language()
    {
        $this->sigmie->newIndex('sigmie')
            ->language(new German)
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('analyzer', $data['settings']['index']['analysis']);
        $this->assertArrayHasKey('default', $data['settings']['index']['analysis']['analyzer']);

        $this->assertArrayHasKey('german_stopwords', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stop',
            'stopwords' => '_german_',
            'class' => GermanStopwords::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['german_stopwords']);

        $this->assertArrayHasKey('german_stemmer', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stemmer',
            'language' => 'light_german',
            'class' => GermanStemmer::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['german_stemmer']);
    }

    /**
     * @test
     */
    public function greek_language()
    {
        $this->sigmie->newIndex('sigmie')
            ->language(new Greek)
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('analyzer', $data['settings']['index']['analysis']);
        $this->assertArrayHasKey('default', $data['settings']['index']['analysis']['analyzer']);

        $this->assertArrayHasKey('greek_stopwords', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stop',
            'stopwords' => '_greek_',
            'class' => GreekStopwords::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['greek_stopwords']);

        $this->assertArrayHasKey('greek_lowercase', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'lowercase',
            'language' => 'greek',
            'class' => Lowercase::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['greek_lowercase']);

        $this->assertArrayHasKey('greek_stemmer', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stemmer',
            'language' => 'greek',
            'class' => GreekStemmer::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['greek_stemmer']);
    }

    /**
     * @test
     */
    public function english_language()
    {
        $this->sigmie->newIndex('sigmie')
            ->language(new English)
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('analyzer', $data['settings']['index']['analysis']);
        $this->assertArrayHasKey('default', $data['settings']['index']['analysis']['analyzer']);

        $this->assertArrayHasKey('english_stopwords', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stop',
            'stopwords' => '_english_',
            'class' => EnglishStopwords::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['english_stopwords']);

        $this->assertArrayHasKey('english_stemmer', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stemmer',
            'language' => 'english',
            'class' => EnglishStemmer::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['english_stemmer']);

        $this->assertArrayHasKey('english_possessive_stemmer', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stemmer',
            'language' => 'possessive_english',
            'class' => PossessiveStemmer::class,
            'priority' => '0'
        ], $data['settings']['index']['analysis']['filter']['english_possessive_stemmer']);
    }

    /**
     * @test
     */
    public function two_way_synonyms()
    {
        $this->sigmie->newIndex('sigmie')
            ->twoWaySynonyms('sigmie_two_way_synonyms', [
                ['treasure', 'gem', 'gold', 'price'],
                ['friend', 'buddy', 'partner']
            ])
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('sigmie_two_way_synonyms', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'synonym',
            'class' => TwoWaySynonyms::class,
            'priority' => '2',
            'synonyms' => [
                'treasure, gem, gold, price',
                'friend, buddy, partner'
            ]
        ], $data['settings']['index']['analysis']['filter']['sigmie_two_way_synonyms']);
    }

    /**
     * @test
     */
    public function one_way_synonyms()
    {
        $this->sigmie->newIndex('sigmie')
            ->oneWaySynonyms('sigmie_one_way_synonyms', [
                'ipod' => ['i-pod', 'i pod']
            ])
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('sigmie_one_way_synonyms', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'synonym',
            'class' => OneWaySynonyms::class,
            'priority' => '3',
            'synonyms' => [
                'i-pod, i pod => ipod',
            ],
        ], $data['settings']['index']['analysis']['filter']['sigmie_one_way_synonyms']);
    }

    /**
     * @test
     */
    public function stopwords()
    {
        $this->sigmie->newIndex('sigmie')
            ->stopwords('sigmie_stopwords', ['about', 'after', 'again'])
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('sigmie_stopwords', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stop',
            'class' => Stopwords::class,
            'priority' => '1',
            'stopwords' => [
                'about', 'after', 'again'
            ]
        ], $data['settings']['index']['analysis']['filter']['sigmie_stopwords']);
    }

    /**
     * @test
     */
    public function stemming()
    {
        $this->sigmie->newIndex('sigmie')
            ->stemming([
                'am' => ['be', 'are'],
                'mouse' => ['mice'],
                'feet' => ['foot'],
            ], 'sigmie_stemmer_overrides')
            ->withoutMappings()->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('sigmie_stemmer_overrides', $data['settings']['index']['analysis']['filter']);
        $this->assertEquals([
            'type' => 'stemmer_override',
            'class' => Stemmer::class,
            'priority' => '4',
            'rules' => [
                'be, are => am',
                'mice => mouse',
                'foot => feet',
            ]
        ], $data['settings']['index']['analysis']['filter']['sigmie_stemmer_overrides']);
    }

    /**
     * @test
     */
    public function analyzer_defaults()
    {
        $this->sigmie->newIndex('sigmie')
            ->withoutMappings()
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('analyzer', $data['settings']['index']['analysis']);
        $this->assertArrayHasKey('default', $data['settings']['index']['analysis']['analyzer']);
        $this->assertArrayHasKey('filter', $data['settings']['index']['analysis']['analyzer']['default']);
        $this->assertEquals('sigmie_tokenizer', $data['settings']['index']['analysis']['analyzer']['default']['tokenizer']);
        $this->assertEmpty($data['settings']['index']['analysis']['analyzer']['default']['filter']);
    }

    /**
     * @test
     */
    public function field_mappings()
    {
        $this->sigmie->newIndex('sigmie')
            ->mappings(function (Blueprint $blueprint) {
                $blueprint->text('title')->searchAsYouType();
                $blueprint->text('content')->unstructuredText();
                $blueprint->number('adults')->integer();
                $blueprint->number('price')->float();
                $blueprint->date('created_at');
                $blueprint->bool('is_valid');
                return $blueprint;
            })
            ->create();

        $data = $this->indexData('sigmie');

        $this->assertArrayHasKey('mappings', $data);
        // $this->assertArrayHasKey('dynamic_templates', $data['mappings']);

        $this->assertArrayHasKey('title', $data['mappings']['properties']);
        $this->assertEquals($data['mappings']['properties']['title']['type'], 'search_as_you_type');

        $this->assertArrayHasKey('content', $data['mappings']['properties']);
        $this->assertEquals($data['mappings']['properties']['content']['type'], 'text');

        $this->assertArrayHasKey('adults', $data['mappings']['properties']);
        $this->assertEquals($data['mappings']['properties']['adults']['type'], 'integer');

        $this->assertArrayHasKey('price', $data['mappings']['properties']);
        $this->assertEquals($data['mappings']['properties']['price']['type'], 'float');

        $this->assertArrayHasKey('created_at', $data['mappings']['properties']);
        $this->assertEquals($data['mappings']['properties']['created_at']['type'], 'date');

        $this->assertArrayHasKey('is_valid', $data['mappings']['properties']);
        $this->assertEquals($data['mappings']['properties']['is_valid']['type'], 'boolean');
    }

    // /**
    //  * @test
    //  */
    // public function without_mappings_creates_dynamic_template()
    // {
    //     $this->sigmie->newIndex('sigmie')->withoutMappings()->create();

    //     $data = $this->indexData('sigmie');

    //     $this->assertArrayHasKey('mappings', $data);
    //     $this->assertArrayHasKey('dynamic_templates', $data['mappings']);
    //     $this->assertNotEmpty($data['mappings']['dynamic_templates']);
    //     $this->assertEquals(
    //         ['sigmie' => [
    //             'match' => '*',
    //             'match_mapping_type' => 'string',
    //             'mapping' => ['analyzer' => 'sigmie_analyzer']
    //         ]],
    //         $data['mappings']['dynamic_templates'][0]
    //     );
    // }

    // /**
    //  * @test
    //  */
    // public function custom_analyzer_is_default()
    // {
    //     $this->sigmie->newIndex('sigmie')->mappings(function (Blueprint $blueprint) {
    //         $blueprint->text('bar')->searchAsYouType();

    //         return $blueprint;
    //     })->create();

    //     $data = $this->indexData('sigmie');

    //     $this->assertArrayHasKey('bar', $data['mappings']['properties']);
    //     $this->assertEquals($data['mappings']['properties']['bar']['analyzer'], 'sigmie_analyzer');
    //     $this->assertArrayHasKey('settings', $data);
    //     $this->assertArrayHasKey('analysis', $data['settings']['index']);
    //     $this->assertArrayHasKey('default', $data['settings']['index']['analysis']);
    //     $this->assertArrayHasKey('type', $data['settings']['index']['analysis']['default']);
    //     $this->assertEquals('sigmie_analyzer', $data['settings']['index']['analysis']['default']['type']);
    // }

    // /**
    //  * @test
    //  */
    // public function custom_analyzer_is_default_with_dynamic_mappings()
    // {
    //     $this->sigmie->newIndex('sigmie')->withoutMappings()->create();

    //     $data = $this->indexData('sigmie');

    //     $this->assertArrayHasKey('settings', $data);
    //     $this->assertArrayHasKey('analysis', $data['settings']['index']);
    //     $this->assertArrayHasKey('default', $data['settings']['index']['analysis']['analyzer']);
    //     $this->assertArrayHasKey('type', $data['settings']['index']['analysis']['analyzer']['default']);
    //     $this->assertEquals('sigmie_analyzer', $data['settings']['index']['analysis']['analyzer']['default']['type']);
    // }

    /**
     * @test
     */
    public function creates_and_index_with_alias()
    {
        $this->sigmie->newIndex('sigmie')
            ->withoutMappings()->create();

        $this->assertIndexExists('sigmie');
    }

    /**
     * @test
     */
    public function index_name_is_current_timestamp()
    {
        Travel::to('2020-01-01 23:59:59');

        $this->sigmie->newIndex('sigmie')->withoutMappings()->create();

        $this->assertIndexExists('sigmie_20200101235959000000');
    }

    /**
     * @test
     */
    public function index_name_prefix()
    {
        Travel::to('2020-01-01 23:59:59');

        $this->sigmie->newIndex('sigmie')
            ->withoutMappings()
            ->shards(4)
            ->replicas(3)
            ->create();

        $index = $this->getIndex('sigmie');

        $this->assertEquals(3, $index->getSettings()->getReplicaShards());
        $this->assertEquals(4, $index->getSettings()->getPrimaryShards());
    }

    private function indexData(string $name): array
    {
        $json = $this->indexAPICall($name, 'GET')->json();
        $indexName = array_key_first($json);
        return $json[$indexName];
    }
}