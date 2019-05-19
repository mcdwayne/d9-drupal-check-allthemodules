<?php

namespace Drupal\Tests\uc_role\Unit\Integration\Event;

use Drupal\rules\Core\RulesEventManager;
use Drupal\Tests\rules\Unit\Integration\Event\EventTestBase;

/**
 * Base class containing common code for uc_role event tests.
 *
 * @group ubercart
 *
 * @requires module rules
 */
abstract class RoleEventTestBase extends EventTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Must enable our module to make our plugins discoverable.
    $this->enableModule('uc_role', [
      'Drupal\\uc_role' => __DIR__ . '/../../../../../src',
    ]);

    // Tell the plugin manager where to look for plugins.
    $this->moduleHandler->getModuleDirectories()
      ->willReturn(['uc_role' => __DIR__ . '/../../../../../']);

    // Create a real plugin manager with a mock moduleHandler.
    $this->eventManager = new RulesEventManager($this->moduleHandler->reveal());
  }

}
