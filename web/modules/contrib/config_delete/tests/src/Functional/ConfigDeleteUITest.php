<?php

namespace Drupal\Tests\config_delete\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user interface for deleting configuration.
 *
 * @group config_delete
 */
class ConfigDeleteUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'automated_cron',
    'config',
    'config_delete',
    'config_delete_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['delete configuration']));
  }

  /**
   * Tests form structure.
   */
  public function testFormStructure() {
    $this->drupalGet('admin/config/development/configuration/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Delete | Drupal');
    $this->assertSession()->selectExists('edit-config-type');
    $this->assertSession()->selectExists('edit-config-name');
    $this->assertSession()->buttonExists(t('Delete'));
  }

  /**
   * Tests config delete.
   */
  public function testConfigDeletion() {
    $config = $this->config('automated_cron.settings');
    $this->assertNotNull($config->get('interval'));

    $form_values = [
      'config_type' => 'system.simple',
      'config_name' => 'automated_cron.settings',
    ];
    $this->drupalPostForm('admin/config/development/configuration/delete', $form_values, 'Delete');
    $this->assertSession()->pageTextContains(t('Configuration "automated_cron.settings" successfully deleted.'));

    $config = $this->config('automated_cron.settings');
    $this->assertFalse($config->get('interval'));
  }

  /**
   * Tests config delete with dependencies.
   */
  public function testConfigDeletionWithDependencies() {
    $config = $this->config('config_delete_test.dep');
    $this->assertEquals(13, $config->get('id'));
    $config2 = $this->config('config_delete_test.dep2');
    $this->assertEquals(4, $config2->get('id'));
    $config3 = $this->config('config_delete_test.dep3');
    $this->assertEquals(1984, $config3->get('id'));

    $form_values = [
      'config_type' => 'system.simple',
      'config_name' => 'config_delete_test.dep',
      'delete_dependencies' => TRUE,
    ];
    $this->drupalPostForm('admin/config/development/configuration/delete', $form_values, 'Delete');
    $this->assertSession()->pageTextContains(t('Configuration "config_delete_test.dep" and all its dependencies successfully deleted.'));

    $config = $this->config('config_delete_test.dep');
    $this->assertFalse($config->get('id'));
    $config2 = $this->config('config_delete_test.dep2');
    $this->assertFalse($config2->get('id'));
    $config3 = $this->config('config_delete_test.dep3');
    $this->assertFalse($config3->get('id'));
  }

  /**
   * Tests form access.
   */
  public function testFormAccess() {
    $this->drupalLogout();
    $this->drupalGet('admin/config/development/configuration/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

}
