<?php

namespace Drupal\Tests\ad_entity\Unit;

use Drupal\ad_entity\TargetingCollection;
use Drupal\Component\Utility\Xss;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TargetingCollection class.
 *
 * @coversDefaultClass \Drupal\ad_entity\TargetingCollection
 * @group ad_entity
 */
class TargetingCollectionTest extends UnitTestCase {

  /**
   * Test the construction of TargetingCollection objects.
   *
   * @covers ::__construct
   * @covers ::toJson
   * @covers ::toArray
   */
  public function testConstructor() {
    $as_array = ['testkey' => 'testval', 'testkey2' => ['testval', 'testval2']];
    $as_json = json_encode($as_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    $collection1 = new TargetingCollection($as_array);
    $collection2 = new TargetingCollection($as_json);
    $this->assertArrayEquals($collection1->toArray(), $collection2->toArray());
    $this->assertEquals($as_json, $collection1->toJson());
    $this->assertEquals('testval', $collection1->get('testkey'));
    $this->assertEquals('testval', $collection2->get('testkey'));
    $this->assertArrayEquals(['testval', 'testval2'], $collection1->get('testkey2'));
    $this->assertArrayEquals(['testval', 'testval2'], $collection2->get('testkey2'));

    // Check against an invalid format.
    $collection1 = new TargetingCollection('invalid');
    $collection2 = new TargetingCollection([]);
    $this->assertArrayEquals($collection1->toArray(), $collection2->toArray());
  }

  /**
   * Test the basic TargetingCollection methods.
   *
   * @covers ::get
   * @covers ::set
   * @covers ::add
   * @covers ::remove
   */
  public function testBasic() {
    $collection = new TargetingCollection();
    $this->assertNull($collection->get('testkey'));
    $collection->set('testkey', 'testval');
    $this->assertEquals('testval', $collection->get('testkey'));
    // When being set multiple times with the same key,
    // uniqueness must be ensured.
    $collection->set('testkey', 'testval');
    $this->assertEquals('testval', $collection->get('testkey'));
    $collection->add('testkey', 'testval');
    $this->assertEquals('testval', $collection->get('testkey'));

    // Do not lose any values.
    $collection->add('testkey', 'testval2');
    $this->assertArrayEquals(['testval', 'testval2'], $collection->get('testkey'));

    $collection->remove('testkey', 'testval');
    $this->assertEquals('testval2', $collection->get('testkey'));
    $collection->remove('testkey', 'testval2');
    $this->assertNull($collection->get('testkey'));
    $collection->add('testkey', 'testval');
    $this->assertNotNull($collection->get('testkey'));
    $collection->remove('testkey');
    $this->assertNull($collection->get('testkey'));
  }

  /**
   * Test the transformation methods.
   *
   * @covers ::collectFromUserInput
   * @covers ::collectFromCollection
   * @covers ::collectFromJson
   * @covers ::toUserOutput
   * @covers ::toJson
   * @covers ::toArray
   */
  public function testTransformationMethods() {
    $collection = new TargetingCollection();
    $input_ok = 'testkey: testval, testkey:testval2,   testkey2: testval, testkey: testval, tokenkey: [token:val], [token:key]:tokenval';
    $collection->collectFromUserInput($input_ok);
    $this->assertSame(4, count($collection->toArray()));
    $this->assertSame(2, count($collection->get('testkey')));
    $this->assertEquals('testval', $collection->get('testkey2'));
    $this->assertEquals('testkey: testval, testkey: testval2, testkey2: testval, tokenkey: [token:val], category: [token:key]:tokenval', $collection->toUserOutput());

    $collection = new TargetingCollection();
    $input_dangerous = 'testkey: <script>alert("Hi there.");</script>';
    $collection->collectFromUserInput($input_dangerous);
    $collection->filter(NULL, FALSE);
    $expected = trim(Xss::filter(strip_tags('<script>alert("Hi there.");</script>')));
    $this->assertEquals($expected, $collection->get('testkey'));

    $collection = new TargetingCollection();
    $input = 'test';
    $collection->collectFromUserInput($input);
    $this->assertEquals('test', $collection->get('category'));
    $this->assertSame(1, count($collection->toArray()));
    $this->assertEquals('category: test', $collection->toUserOutput());

    $collection = new TargetingCollection();
    $collection->collectFromCollection(new TargetingCollection());
    $this->assertTrue($collection->isEmpty());
    $collection->add('testkey', 'testval');
    $collection2 = new TargetingCollection();
    $collection2->add('testkey', 'testval');
    $collection2->add('testkey', 'testval2');
    $collection->collectFromCollection($collection2);
    $this->assertArrayEquals(['testkey' => ['testval', 'testval2']], $collection->toArray());
    $this->assertArrayEquals(['testkey' => ['testval', 'testval2']], $collection2->toArray());

    $collection = new TargetingCollection();
    $collection->collectFromJson($collection2->toJson());
    $this->assertArrayEquals(['testkey' => ['testval', 'testval2']], $collection->toArray());
  }

  /**
   * Test the emptiness of the TargetingCollection.
   *
   * @covers ::isEmpty
   */
  public function testEmptiness() {
    $collection = new TargetingCollection();
    $this->assertSame(TRUE, $collection->isEmpty());
    $this->assertArrayEquals([], $collection->toArray());
    $this->assertNull($collection->get('testkey'));
    $collection->set('testkey', 'testval');
    $this->assertSame(FALSE, $collection->isEmpty());
    $collection->remove('testkey', 'testval2');
    $this->assertSame(FALSE, $collection->isEmpty());
    $collection->remove('testkey2');
    $this->assertSame(FALSE, $collection->isEmpty());
    $collection->remove('testkey', 'testval');
    $this->assertTrue($collection->isEmpty());
  }

}
