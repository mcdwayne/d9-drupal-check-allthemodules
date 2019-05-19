<?php

namespace Drupal\simple_analytics\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test uninstall functionality of Simple Analytics module.
 *
 * @group Simple Analytics
 */
class SimpleAnalyticsUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simple_analytics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer modules',
      'simple_analytics admin',
    ];

    // User to set up simple_analytics.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if the module cleans up the disk on uninstall.
   */
  public function testSimpleAnalyticsUninstall() {

    // Uninstall the module.
    $edit = [];
    $edit['uninstall[simple_analytics]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

  }

}
