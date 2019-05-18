<?php

namespace Drupal\Tests\elastic_search\Kernel\Plugin\FieldMapper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\elastic_search\Elastic\ElasticDocumentManager;
use Drupal\elastic_search\Plugin\FieldMapper\SimpleReference;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;

/**
 * SimpleReferenceTest
 *
 * @group elastic_search
 */
class SimpleReferenceTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test a child only index, which will return nothing
   */
  public function testIndexGeneratorChildOnly() {

    $etm = \Mockery::mock(EntityTypeManagerInterface::class);
    $edm = \Mockery::mock(ElasticDocumentManager::class);

    $testType = 'test_instance';
    $base = new SimpleReference([], $testType, [],$etm, $edm);

    $testArray = [];
    $values = ['val1', 'val2', 'val3'];
    foreach ($values as $value) {
      $testArray[] = ['target_id' => $value];
    }
    static::assertEquals($values, $base->normalizeFieldData('test_id', $testArray, ['nested' => '1']));
    static::assertEquals($values[0], $base->normalizeFieldData('test_id', $testArray, ['nested' => '0']));

  }

}
