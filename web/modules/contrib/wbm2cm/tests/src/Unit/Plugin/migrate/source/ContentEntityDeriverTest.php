<?php

namespace Drupal\Tests\wbm2cm\Unit\Plugin\migrate\source;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wbm2cm\Plugin\migrate\source\ContentEntityDeriver;

/**
 * @coversDefaultClass \Drupal\wbm2cm\Plugin\migrate\source\ContentEntityDeriver
 *
 * @group wbm2cm
 */
class ContentEntityDeriverTest extends UnitTestCase {

  /**
   * @test
   * @covers ::getDerivativeDefinitions
   */
  public function getDerivativeDefinitions() {
    $definitions = [];

    $entity_type = $this->prophesize(ContentEntityTypeInterface::class);
    $entity_type->getProvider()->willReturn('wbm2cm');
    $definitions['content'] = $entity_type->reveal();
    $definitions['config'] = $this->prophesize(ConfigEntityTypeInterface::class)->reveal();

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinitions()->willReturn($definitions);

    $deriver = new ContentEntityDeriver($entity_type_manager->reveal());
    $definitions = $deriver->getDerivativeDefinitions([]);
    $this->assertArrayHasKey('content', $definitions);
    $this->assertSame('wbm2cm', $definitions['content']['source_module']);
    $this->assertArrayNotHasKey('config', $definitions);
  }

}
