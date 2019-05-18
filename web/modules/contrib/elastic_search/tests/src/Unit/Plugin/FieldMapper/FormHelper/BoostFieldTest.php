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
use Drupal\elastic_search\Plugin\FieldMapper\FormHelper\BoostField;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group elastic_search
 */
class BoostFieldTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test that the analyzer field options are built correctly
   */
  public function testBoostFieldTrait() {

    $instance = new HasBoostFieldTrait();

    $this->assertEquals('boost', $instance->getBoostFieldIdProxy());
    $this->assertEquals(0.0, $instance->getBoostFieldDefaultProxy());

    $field = $instance->getBoostFieldProxy();

    $this->assertArrayHasKey($instance->getBoostFieldIdProxy(), $field);
    $this->assertArrayHasKey('#type', $field[$instance->getBoostFieldIdProxy()]);
    $this->assertEquals('number', $field[$instance->getBoostFieldIdProxy()]['#type']);

  }

}

/**
 * Class HasTrait
 *
 * @package Drupal\Tests\elastic_search\Unit\Plugin\FieldMapper\FormHelper
 */
class HasBoostFieldTrait {

  use BoostField;

  /**
   * @inheritDoc
   */
  protected function t($string, array $args = [], array $options = []) {
  }

  /**
   * @return string
   */
  public function getBoostFieldIdProxy(): string {
    return $this->getBoostFieldId();
  }

  /**
   * @param float $default
   *
   * @return array
   */
  public function getBoostFieldProxy(float $default = 0.5): array {
    return $this->getBoostField($default);
  }

  /**
   * @return float
   */
  public function getBoostFieldDefaultProxy(): float {
    return $this->getBoostFieldDefault();
  }

}