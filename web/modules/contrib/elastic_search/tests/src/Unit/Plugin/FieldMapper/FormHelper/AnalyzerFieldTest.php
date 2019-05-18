<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.05.17
 * Time: 00:34
 */

namespace Drupal\Tests\elastic_search\Unit\Plugin\FieldMapper\FormHelper;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\AnalyzerField;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group elastic_search
 */
class AnalyzerFieldTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test that the analyzer field options are built correctly
   */
  public function testAnalyzerFieldProcessor() {

    $instance = new HasAnalyzerFieldTrait();
    $this->assertEquals(HasAnalyzerFieldTrait::$NONE_IDENTIFIER, $instance->getAnalyzerFieldDefaultProxy());

    $esi = \Mockery::mock(EntityStorageInterface::class);
    $esi->shouldReceive('loadMultiple')->andReturn(['number_1' => 'thing', 'number_2' => 'whatevs']);

    $result = [
      HasAnalyzerFieldTrait::$NONE_IDENTIFIER => HasAnalyzerFieldTrait::$NONE_IDENTIFIER,
      'number_1'                              => 'number_1',
      'number_2'                              => 'number_2',
    ];
    $options = $instance->getAnalyzerOptionsProxy($esi);
    self::assertEquals($result,$options);

  }

}

/**
 * Class HasTrait
 *
 * @package Drupal\Tests\elastic_search\Unit\Plugin\FieldMapper\FormHelper
 */
class HasAnalyzerFieldTrait {

  use AnalyzerField;

  /**
   * @inheritDoc
   */
  protected function t($string, array $args = [], array $options = []) {
  }

  /**
   * @return string
   */
  public function getAnalyzerFieldDefaultProxy(): string {
    return $this->getAnalyzerFieldDefault();
  }

  /**
   * @param \Drupal\Core\Entity\EntityStorageInterface $analyzerStorage
   */
  public function getAnalyzerOptionsProxy(EntityStorageInterface $analyzerStorage) {
    return $this->getAnalyzerOptions($analyzerStorage);
  }

}