<?php

namespace Drupal\Tests\images_optimizer\Unit\HookHandler;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\images_optimizer\Entity\ImagesOptimizerImageStyle;
use Drupal\images_optimizer\HookHandler\EntityTypeAlterHookHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test class for the EntityTypeAlterHookHandler class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\HookHandler
 */
class EntityTypeAlterHookHandlerTest extends UnitTestCase {

  /**
   * The entity type alter hook handler to test.
   *
   * @var \Drupal\images_optimizer\HookHandler\EntityTypeAlterHookHandler
   */
  private $entityTypeAlterHookHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeAlterHookHandler = new EntityTypeAlterHookHandler();
  }

  /**
   * Test process() when the image style entity type is not defined.
   */
  public function testProcessWhenTheImageStyleEntityTypeIsNotDefined() {
    $entity_types = [];
    $this->assertFalse($this->entityTypeAlterHookHandler->process($entity_types));
  }

  /**
   * Test process().
   */
  public function testProcess() {
    $config_entity_type = new ConfigEntityType(['id' => 'image_style']);
    $entity_types = ['image_style' => $config_entity_type];
    $this->assertTrue($this->entityTypeAlterHookHandler->process($entity_types));
    $this->assertSame(ImagesOptimizerImageStyle::class, $config_entity_type->getClass());
  }

}
