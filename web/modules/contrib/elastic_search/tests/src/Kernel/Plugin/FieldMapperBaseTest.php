<?php

namespace Drupal\Tests\elastic_search\Kernel\Elastic;

use Drupal\elastic_search\Plugin\FieldMapperBase;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * FieldMapperBaseTest
 *
 * @group elastic_search
 */
class FieldMapperBaseTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * @var array
   */
  public static $modules = [
    'elastic_search',
  ];

  /**
   * Test a child only index, which will return nothing
   */
  public function testBase() {

    $testType = 'test_instance';
    $base = new BaseInstance([], $testType, []);

    static::assertEquals($testType, $base->getElasticType());
    //static::assertEquals(TRUE, is_array($base->getFormFields([])));
    static::assertEquals(FALSE, $base->supportsFields());
    static::assertNull($base->normalizeFieldData('test_id', [], []));
    $testValue = 'test_value';
    static::assertEquals($testValue, $base->normalizeFieldData('test_id', [['value' => $testValue]], []));

    $testArray = [];
    $values = ['val1', 'val2', 'val3'];
    foreach ($values as $value) {
      $testArray[] = ['value' => $value];
    }
    static::assertEquals($values, $base->normalizeFieldData('test_id', $testArray, ['nested' => '1']));
    static::assertEquals($values[0], $base->normalizeFieldData('test_id', $testArray, ['nested' => '0']));

    //Testing getDslFromData is a bit more involved as we need to test some specific conversion functions it runs
    $dslData = [
      'should_be_bool'        => 0,
      'should_be_true'        => 1,
      'will_become_true'      => 42,
      'integer_of_the_beast'  => '666',
      'float_numeric'         => '23.11',
      'should_be_removed'     => '',
      'should_not_be_removed' => 'i am a string',
    ];
    $processed = $base->getDslFromData($dslData);

    static::assertInternalType('float', $processed['float_numeric']);
    static::assertInternalType('int', $processed['integer_of_the_beast']);
    static::assertInternalType('bool', $processed['should_be_bool']);
    static::assertInternalType('bool', $processed['should_be_true']);
    static::assertInternalType('bool', $processed['will_become_true']);
    static::assertInternalType('string', $processed['should_not_be_removed']);
    static::assertArrayNotHasKey('should_be_removed', $processed);

    static::assertEquals(23.11, $processed['float_numeric']);
    static::assertEquals(666, $processed['integer_of_the_beast']);
    static::assertEquals(FALSE, $processed['should_be_bool']);
    static::assertEquals(TRUE, $processed['should_be_true']);
    static::assertEquals(TRUE, $processed['will_become_true']);
    static::assertEquals('i am a string', $processed['should_not_be_removed']);

  }

}

class BaseInstance extends FieldMapperBase {

  /**
   * @inheritDoc
   */
  public function getSupportedTypes() {
    return [];
  }

}