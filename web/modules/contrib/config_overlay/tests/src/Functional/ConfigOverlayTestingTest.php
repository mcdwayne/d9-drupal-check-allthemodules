<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests installation of the Testing profile with Configuration Overlay.
 *
 * @group config_overlay
 */
class ConfigOverlayTestingTest extends BrowserTestBase {

  use ConfigOverlayTestingTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_overlay'];

  /**
   * A list of collections for this test's configuration.
   *
   * @var string[]
   */
  protected $collections = [StorageInterface::DEFAULT_COLLECTION];

}
