<?php

namespace PragmaRX\Countries\Tests;

use PHPUnit\Framework\TestCase;
use PragmaRX\Countries\Package\Support\Collection;

class CollectionTest extends TestCase
{
    public function testBasicCollectionFunctionality()
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->assertEquals(3, $collection->count());
        $this->assertEquals(1, $collection->first());
        $this->assertEquals(3, $collection->last());
    }

    public function testObjectLikePropertyAccess()
    {
        $data = [
            'name' => [
                'common' => 'Brazil',
                'official' => 'Federative Republic of Brazil',
            ],
            'currencies' => [
                'BRL' => [
                    'name' => 'Brazilian real',
                    'symbol' => 'R$',
                ],
            ],
        ];

        $collection = new Collection($data);

        // Test nested object access
        $this->assertEquals('Brazil', $collection->name->common);
        $this->assertEquals('Federative Republic of Brazil', $collection->name->official);
        $this->assertEquals('Brazilian real', $collection->currencies->BRL->name);
        $this->assertEquals('R$', $collection->currencies->BRL->symbol);
    }

    public function testCaseInsensitiveAccess()
    {
        $data = [
            'EUR' => [
                'name' => 'Euro',
                'symbol' => '€',
            ],
        ];

        $collection = new Collection($data);

        // Test case-insensitive access
        $this->assertEquals('Euro', $collection->EUR->name);
        $this->assertEquals('Euro', $collection->eur->name);
        $this->assertEquals('€', $collection->EUR->symbol);
        $this->assertEquals('€', $collection->eur->symbol);
    }

    public function testDynamicPropertyNameConversion()
    {
        $data = [
            'Europe/Paris' => [
                'zone_name' => 'Europe/Paris',
                'abbreviations' => ['CET', 'CEST'],
            ],
            'Asia/Tokyo' => [
                'zone_name' => 'Asia/Tokyo',
                'abbreviations' => ['JST'],
            ],
        ];

        $collection = new Collection($data);

        // Test dynamic property name conversion (underscore to slash)
        $this->assertEquals('Europe/Paris', $collection->europe_paris->zone_name);
        $this->assertEquals('Asia/Tokyo', $collection->asia_tokyo->zone_name);
        $this->assertEquals(['CET', 'CEST'], $collection->europe_paris->abbreviations->toArray());
        $this->assertEquals(['JST'], $collection->asia_tokyo->abbreviations->toArray());
    }

    public function testArrayAccess()
    {
        $data = [
            'states' => [
                'BA' => [
                    'region' => 'Puglia',
                ],
                'TP' => [
                    'region' => 'Sicilia',
                ],
            ],
        ];

        $collection = new Collection($data);

        // Test array access notation
        $this->assertEquals('Puglia', $collection->states['BA']['region']);
        $this->assertEquals('Sicilia', $collection->states['TP']['region']);
    }

    public function testOverwriteMethod()
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);

        // Test overwrite with array
        $collection->overwrite(['b' => 3, 'c' => 4]);

        $this->assertEquals(1, $collection->get('a'));
        $this->assertEquals(3, $collection->get('b')); // overwritten
        $this->assertEquals(4, $collection->get('c')); // new

        // Test overwrite with another Collection
        $otherCollection = new Collection(['d' => 5, 'a' => 10]);
        $collection->overwrite($otherCollection);

        $this->assertEquals(10, $collection->get('a')); // overwritten again
        $this->assertEquals(5, $collection->get('d')); // new
    }

    public function testOverwriteWithInvalidData()
    {
        $collection = new Collection(['a' => 1]);
        $originalCount = $collection->count();

        // Test overwrite with non-array/non-collection data
        $result = $collection->overwrite('invalid');

        $this->assertSame($collection, $result); // should return same instance
        $this->assertEquals($originalCount, $collection->count()); // should not change
    }

    public function testMagicSetAndIsset()
    {
        $collection = new Collection();

        // Test magic set
        $collection->newProperty = 'test value';
        $this->assertEquals('test value', $collection->get('newProperty'));

        // Test magic isset
        $this->assertTrue(isset($collection->newProperty));
        $this->assertFalse(isset($collection->nonExistentProperty));
    }

    public function testNestedCollectionWrapping()
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value',
                ],
            ],
        ];

        $collection = new Collection($data);

        // Test that nested arrays are wrapped in Collection instances
        $this->assertInstanceOf(Collection::class, $collection->level1);
        $this->assertInstanceOf(Collection::class, $collection->level1->level2);
        $this->assertEquals('deep value', $collection->level1->level2->level3);
    }

    public function testPropertyAccessReturnsNullForNonExistent()
    {
        $collection = new Collection(['existing' => 'value']);

        $this->assertEquals('value', $collection->existing);
        $this->assertNull($collection->nonExistent);
        $this->assertNull($collection->also_non_existent);
    }

    public function testWhereMethodsStillWork()
    {
        $data = [
            ['name' => 'Brazil', 'code' => 'BR'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Italy', 'code' => 'IT'],
        ];

        $collection = new Collection($data);

        $result = $collection->where('name', 'Brazil');
        $this->assertEquals(1, $result->count());
        $this->assertEquals('Brazil', $result->first()['name']);
    }

    public function testCollectionMethodsReturnCollectionInstances()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);

        $filtered = $collection->filter(function ($item) {
            return $item > 3;
        });

        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertEquals([4, 5], $filtered->values()->toArray());
    }

    public function testComplexNestedAccess()
    {
        $data = [
            'currencies' => [
                'EUR' => [
                    'coins' => [
                        'frequent' => ['€1', '€2', '50c'],
                    ],
                    'banknotes' => [
                        'frequent' => ['€5', '€10', '€20'],
                    ],
                ],
            ],
        ];

        $collection = new Collection($data);

        // Test complex nested access as seen in the original tests
        $this->assertEquals('€1', $collection->currencies->EUR->coins->frequent->first());
        $this->assertEquals('50c', $collection->currencies->EUR->coins->frequent->last());
        $this->assertEquals('€1', $collection->currencies->eur->coins->frequent->first());
    }
}
