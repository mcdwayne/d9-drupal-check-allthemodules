<?php

namespace Drupal\Tests\plus\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\plus\Utility\ArrayObject;

/**
 * @coversDefaultClass Drupal\plus\Utility\ArrayObject
 *
 * @group Utility
 */
class ArrayObjectTest extends UnitTestCase {

  /**
   * Tests the constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $array = new ArrayObject();
    $this->assertInstanceOf(ArrayObject::class, $array);
  }

  /**
   * Tests the static create method.
   *
   * @covers ::__get
   * @covers ::__set
   * @covers ::create
   * @covers ::get
   * @covers ::offsetGet
   * @covers ::offsetSet
   * @covers ::set
   */
  public function testCreate() {
    $original = ['prop' => 123];

    $obj = ArrayObject::create($original);

    foreach ([1, 2, 3, 4] as $value) {
      switch ($value) {
        // Test setting by property.
        case 1:
          $obj->prop = $value;
          break;

        // Test setting by array index.
        case 2:
          $obj['prop'] = $value;
          break;

        // Test setting by method.
        case 3:
          $obj->set('prop', $value);
          break;

        // Test setting by reference.
        case 4:
          $ref =& $obj->prop;
          $ref = $value;
          break;
      }

      // Ensure ::get returns the correct value.
      $this->assertSame($value, $obj->get('prop'));

      // Ensure ::__get returns the correct value.
      $this->assertSame($value, $obj->prop);

      // Ensure ::offsetGet returns the correct value.
      $this->assertSame($value, $obj['prop']);
    }

    // Ensure the original array has not changed.
    $this->assertSame(123, $original['prop']);
  }

  /**
   * Tests the static create method.
   *
   * @covers ::__get
   * @covers ::__set
   * @covers ::create
   * @covers ::get
   * @covers ::offsetGet
   * @covers ::offsetSet
   * @covers ::set
   */
  public function testReference() {
    $original = ['prop' => 123];

    $obj = ArrayObject::reference($original);

    foreach ([1, 2, 3, 4] as $value) {
      switch ($value) {
        // Test setting by property.
        case 1:
          $obj->prop = $value;
          break;

        // Test setting by array index.
        case 2:
          $obj['prop'] = $value;
          break;

        // Test setting by method.
        case 3:
          $obj->set('prop', $value);
          break;

        // Test setting by reference.
        case 4:
          $ref =& $obj->prop;
          $ref = $value;
          break;
      }

      // Ensure ::get returns the correct value.
      $this->assertSame($value, $obj->get('prop'));

      // Ensure ::__get returns the correct value.
      $this->assertSame($value, $obj->prop);

      // Ensure ::offsetGet returns the correct value.
      $this->assertSame($value, $obj['prop']);
    }

    // Ensure the original array has the same value as the last value from loop.
    $this->assertSame($value, $original['prop']);
  }

  /**
   * Tests the flatten method.
   *
   * @covers ::flatten
   */
  public function testFlatten() {
    $array = ArrayObject::create(['one', ['two'], [['three']], 4]);
    $this->assertSame(['one', ['two'], [['three']], 4], $array->value());
    $this->assertSame(['one', 'two', 'three', 4], $array->flatten()->value());
  }

}
