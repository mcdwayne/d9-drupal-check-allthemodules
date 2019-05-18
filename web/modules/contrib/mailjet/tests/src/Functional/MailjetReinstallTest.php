<?php

namespace Drupal\Tests\mailjet\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests of the mailjet module.
 *
 * @ingroup mailjet
 *
 * @group simpletest_example
 * @group examples
 */
class MailjetReinstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['mailjet'];

  /**
   * Verify that we can uninstall and then reinstall mailjet.
   *
   * Since mailjet installs configuration objects, it needs to clean
   * up after itself. This test verifies that it does.
   *
   */
  public function testUninstallReinstall() {
    $session = $this->assertSession();

    /* @var $module_installer \Drupal\Core\Extension\ModuleInstallerInterface */
    $module_installer = $this->container->get('module_installer');
    $module_installer->uninstall(['mailjet']);
    $this->drupalGet('admin/config/system/mailjet');
    $session->statusCodeEquals(404);

    // We reinstall the mailjet module to make sure it happens
    $module_installer->install(['mailjet']);
    $this->drupalGet('admin/config/system/mailjet');
    $session->statusCodeEquals(200);
  }

} 

