<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 10.02.17
 * Time: 15:06
 */

namespace Drupal\Tests\elastic_search\Unit\Event;

use Drupal\elastic_search\Event\FieldMapperEventBase;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Test field mapper event base
 *
 * @group elastic_search
 */
class FieldMapperEventBaseTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test the base methods on the field mapper event
   */
  public function testFieldMapperEventBase() {
    /** @var FieldMapperEventBase $event */
    $supported = ['text', 'test'];
    $event = \Mockery::mock(FieldMapperEventBase::class, ['mock', $supported])
                     ->makePartial();
    $this->assertEquals('mock', $event->getId());
    $this->assertEquals($supported, $event->getSupported());

    $supported2 = ['nothing', 'nobody', 'no-one'];
    $event->setSupported($supported2);
    $this->assertEquals($supported2, $event->getSupported());
  }

}
