<?php

namespace Drupal\Tests\config_delete\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the user interface for deleting configuration.
 *
 * @group config_delete
 */
class ConfigDeleteUITest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contact', 'config', 'config_delete'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['delete configuration']));
  }

  /**
   * Tests config delete.
   */
  public function testConfigDeletion() {
    $this->drupalGet('admin/config/development/configuration/delete');
    $config = $this->config('contact.form.personal');
    $this->assertNotNull($config->get('id'));

    $this->getSession()->getPage()->selectFieldOption('config_type', 'contact_form');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('config_name', 'personal');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('edit-submit');
    $this->assertSession()->pageTextContains(t('Configuration "contact.form.personal" successfully deleted.'));

    $this->rebuildContainer();
    $config = $this->config('contact.form.personal');
    $this->assertFalse($config->get('id'), $config->get('id'));
  }

}
