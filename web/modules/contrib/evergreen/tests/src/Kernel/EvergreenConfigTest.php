<?php

namespace Drupal\Tests\evergreen\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\evergreen\Entity\EvergreenConfig;

/**
 * Tests the new entity API for evergreen content.
 *
 * @group evergreen
 * @SuppressWarnings(StaticAccess)
 */
class EvergreenConfigTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['evergreen'];

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test EvergreenConfig::checkBundle()
   */
  public function testCheckBundle() {
    $config = EvergreenConfig::create();
    $config->set('evergreen_bundle', 'node.page');
    $config->checkBundle();
    $this->assertEquals('page', $config->getEvergreenBundle());
  }

  /**
   * Test EvergreenConfig::checkExpiry()
   */
  public function testCheckExpiry() {
    $config = EvergreenConfig::create();
    $config->set('evergreen_expiry', '10 days');
    $config->checkExpiry();
    $this->assertEquals(10 * (60 * 60 * 24), $config->getEvergreenExpiry());
  }

  /**
   * Test EvergreenConfig::generateId()
   */
  public function testGenerateId() {
    $config = EvergreenConfig::create();
    $config->set('evergreen_entity_type', 'node');
    $config->set('evergreen_bundle', 'page');
    $config->generateId();
    $this->assertEquals('node.page', $config->id());
  }

}
