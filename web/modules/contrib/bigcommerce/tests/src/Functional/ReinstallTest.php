<?php

namespace Drupal\Tests\bigcommerce\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests uninstalling and reinstalling the module.
 *
 * @group bigcommerce
 */
class ReinstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bigcommerce',
  ];

  /**
   * Tests uninstalling and reinstalling the module.
   */
  public function testReinstall() {
    $this->drupalLogin($this->createUser(['administer modules']));
    $edit = ['uninstall[bigcommerce]' => TRUE];
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPostForm(NULL, [], t('Uninstall'));
    $this->rebuildContainer();
    $this->assertFalse($this->container->get('module_handler')->moduleExists('bigcommerce'), 'BigCommerce module uninstalled.');
    $edit = ["modules[bigcommerce][enable]" => TRUE];
    $this->drupalPostForm('admin/modules', $edit, t('Install'));
    $this->rebuildContainer();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('bigcommerce'), 'BigCommerce module has been installed.');
  }

}
