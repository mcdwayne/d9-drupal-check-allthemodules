<?php

namespace Drupal\Tests\switches\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\switches\Entity\SwitchInterface;

/**
 * Test generic switch entity functionality.
 *
 * @group switches
 */
class SwitchEntityTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Core modules.
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',

    // This module.
    'switches',
  ];

  /**
   * The switch manager service.
   *
   * @var \Drupal\switches\SwitchManager
   */
  protected $switchManager;

  /**
   * The conditions manager service.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionsManager;

  /**
   * The switch entity storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $switchStorage;

  /**
   * A switch entity to be tested.
   *
   * @var \Drupal\switches\Entity\SwitchInterface
   */
  protected $switch;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->switchManager = $this->container->get('switches.manager');
    $this->conditionsManager = $this->container->get('plugin.manager.condition');
    $this->switchStorage = $this->container->get('entity_type.manager')
      ->getStorage('switch');
  }

  /**
   * Test that a basic switch may be created.
   */
  public function testBasicSwitchCreation() {
    // Create the switch instance.
    $this->switch = $this->switchStorage->create([
      'id' => $this->randomMachineName(),
      'name' => $this->randomString(),
      'defaultValue' => TRUE,
    ]);

    // Verify we created a Switch entity.
    $this->assertInstanceOf(SwitchInterface::class, $this->switch, 'A valid switch entity was not created.');

    // We should get the default value for the activation status since there
    // were no activation conditions configured.
    $this->assertTrue($this->switch->getActivationStatus(), 'The default activation status was not returned as expected for a configured switch.');
  }

  /**
   * Test that a switch with conditions may be created.
   */
  public function testConditionsSwitchCreation() {
    // @todo How can we test using conditions with contexts?
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

}
