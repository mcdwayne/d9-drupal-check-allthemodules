<?php

namespace Drupal\Tests\wbm2cm\Unit\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\wbm2cm\Plugin\migrate\process\SetNull;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\wbm2cm\Plugin\migrate\process\SetNull
 * @group wbm2cm
 */
class SetNullTest extends UnitTestCase {

  /**
   * @covers ::transform
   */
  public function testTransform() {
    $plugin = new SetNull([], 'unset', []);

    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = $this->prophesize(Row::class);
    $row->setDestinationProperty('moderation_state', NULL)->shouldBeCalled();

    $plugin->transform(NULL, $executable, $row->reveal(), 'moderation_state');
  }

}
