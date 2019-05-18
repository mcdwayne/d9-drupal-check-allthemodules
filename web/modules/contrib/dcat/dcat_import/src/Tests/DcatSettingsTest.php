<?php

namespace Drupal\dcat_import\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\dcat_import\Entity\DcatSource;

/**
 * Test DCAT settings page.
 *
 * @group dcat_import
 */
class DcatSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dcat_import', 'migrate_plus'];

  /**
   * Tests that the overview page loads with a 200 response.
   */
  public function testFormLoad() {
    $user = $this->drupalCreateUser(['administer dcat sources']);
    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('dcat_import.admin_settings'));
    $this->assertResponse(200);
  }

  /**
   * Test the agent add form.
   */
  public function testFormSave() {
    $user = $this->drupalCreateUser([
      'administer dcat sources'
    ]);
    $edit = [
      'global_theme_iri' => 'http://example.com',
      'global_theme_format' => 'guess',
    ];

    $this->drupalLogin($user);
    $this->drupalGet(Url::fromRoute('dcat_import.admin_settings'));

    // Adding and viewing entity.
    $this->drupalPostForm(Url::fromRoute('dcat_import.admin_settings'), $edit, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
    $this->assertFieldByName('global_theme_iri', 'http://example.com');
    $this->assertFieldByName('global_theme_format', 'guess');
  }

}
