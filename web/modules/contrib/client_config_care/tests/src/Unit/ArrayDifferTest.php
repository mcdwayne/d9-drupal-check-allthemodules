<?php

namespace Drupal\Tests\client_config_care\Unit;

use Drupal\client_config_care\Validator\ArrayDiffer;
use Drupal\Tests\UnitTestCase;

class ArrayDifferTest extends UnitTestCase {

  /**
   * @var ArrayDiffer
   */
  private $configDiff;

  public function setUp()
  {
    parent::setUp();

    $this->configDiff = new ArrayDiffer();
  }

  public function testHasNoDifference() {
    $originalConfig = [
      'a' => 'b',
      'c' => [
        '1' => ['']
      ],
    ];

    $newConfig = [
      'a' => 'b',
      'c' => [
        '1' => ['']
      ],
    ];

    self::assertFalse($this->configDiff->hasDifference($originalConfig, $newConfig));
  }

  public function testHasDifference() {
    $originalConfig = [
      'a' => 'b',
      'c' => [
        '1' => ['']
      ],
    ];

    $newConfig = [
      'a' => 'b',
      'c' => [
        '1' => ['difference']
      ],
    ];

    self::assertTrue($this->configDiff->hasDifference($originalConfig, $newConfig));
  }

}
