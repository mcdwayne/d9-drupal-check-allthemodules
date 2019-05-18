<?php

namespace Drupal\Tests\uc_order\Unit\Integration\Event;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase;

/**
 * Base class containing common code for uc_order event tests.
 *
 * @group ubercart
 *
 * @requires module rules
 */
abstract class OrderEventTestBase extends EventTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('uc_order', [
      'Drupal\\uc_order' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['uc_order' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal());
  }

}
