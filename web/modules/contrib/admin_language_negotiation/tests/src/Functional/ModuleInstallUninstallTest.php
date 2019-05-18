<?php

namespace Drupal\Tests\admin_language_negotiation\Functional;

/**
 * Tests module installation and uninstallation.
 *
 * @group admin_language_negotiation
 */
class ModuleInstallUninstallTest extends AdminLanguageNegotiationTestBase {

  /**
   * Tests installation and uninstallation.
   */
  public function testInstallationAndUninstallation() {
    $module_handler = \Drupal::moduleHandler();
    self::assertTrue($module_handler->moduleExists(reset(static::$modules)));

    /**
     * @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
     */
    $module_installer = \Drupal::service('module_installer');

    $module_installer->uninstall(static::$modules);
    self::assertFalse($module_handler->moduleExists(reset(static::$modules)));
  }

  /**
   * Tests the Uninstall Validation.
   */
  public function testModuleCannotBeRemovedIfNegotiationInUse() {
    $module_handler = \Drupal::moduleHandler();
    /**
     * @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
     */
    $module_installer = \Drupal::service('module_installer');

    // Enabling the language negotiation
    $this->enableNegotiation($this->adminUser);

    $uninstallValidator = $module_installer->validateUninstall(static::$modules);
    self::assertTrue(isset($uninstallValidator['admin_language_negotiation']));
    self::assertTrue($module_handler->moduleExists(reset(static::$modules)), 'Module still installed due to an in-use negotiation method');
  }

}
