<?php

/**
 * @file
 * Test case for testing the Front-End Administration module.
 *
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test feadmin configurability and plugin discovery.
 *
 * @group Front-End Administration
 *
 * @ingroup feadmin
 */
class FeAdminSettingsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('feadmin_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Front-End Administration settings test.',
      'description' => 'Validate plugin discovery and configurations in Front-End Administration module.',
      'group' => 'Front-End Administration',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer feadmin', 'use feadmin', 'use dummy tool'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Check that Front-End administration module configures properly.
   *
   * 1) Check that admin page is available.
   * 2) Check FeAdminTool plugin discovery: two dummy tools available.
   * 3) Check specific plugin configurations are available.
   * 4) Check validation of plugin specific configurations.
   * 5) Check saving of generic configurations.
   * 6) Check saving of specific configurations.
   */
  public function testConfigureModule() {

    // ----------------------------------------------------------------------
    // 1) Check that admin page is available.
    // Open admin UI.
    $this->drupalGet('/admin/config/system/front_end_administration');

    // ----------------------------------------------------------------------
    // 2) Check FeAdminTool plugin discovery: two dummy tools available.
    $this->assertText('Dummy tool 1', 'Dummy tool 1 has been discovered.');
    $this->assertText('Dummy tool 2', 'Dummy tool 2 has been discovered.');

    // ----------------------------------------------------------------------
    // 3) Check specific plugin configurations are available.
    $this->assertFieldByXPath("//details/summary[contains(., 'Dummy 1 Front-End Administration tool, used for testing purpose.')]", NULL, 'Dummy tool 1 has specific configurations');
    $this->assertFieldById('edit-feadmin-dummy-first-dummy-label', 'Dummy label 1', 'Dummy tool 1 has specific label default configuration.');
    $this->assertFieldChecked('edit-feadmin-dummy-first-dummy-option', 'Dummy tool 1 has specific option default configuration.');

    $this->assertText('Dummy 2 Front-End Administration tool, used for testing purpose.', 'Dummy tool 2 has description.');
    $this->assertNoFieldByXPath("//details/summary[contains(., 'Dummy 2 Front-End Administration tool, used for testing purpose.')]", NULL, 'Dummy tool 2 has no specific configurations');

    // ----------------------------------------------------------------------
    // 4) Check validation of plugin specific configurations.
    $edit = array(
      'feadmin_dummy_first[dummy_label]' => 'test',
      'feadmin_dummy_first[dummy_option]' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertText('Dummy tool 1: Your label must contain the word dummy.', 'Specific tool validation is run.');

    // ----------------------------------------------------------------------
    // 5) Check saving of generic configurations.
    $this->assertNoFieldChecked('edit-feadmin-feadmin-dummy-first-enabled-data', 'Dummy tool 1 is not enabled by default.');
    $this->assertNoFieldChecked('edit-feadmin-feadmin-dummy-second-enabled-data', 'Dummy tool 2 is not enabled by default.');

    $edit = array(
      'feadmin[feadmin_dummy_first][weight][data]' => 5,
      'feadmin[feadmin_dummy_first][enabled][data]' => TRUE,
      'feadmin[feadmin_dummy_second][weight][data]' => 4,
      'feadmin[feadmin_dummy_second][enabled][data]' => TRUE,
      'feadmin_dummy_first[dummy_label]' => 'Another dummy label',
      'feadmin_dummy_first[dummy_option]' => FALSE,
    );

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertFieldChecked('edit-feadmin-feadmin-dummy-first-enabled-data', 'Dummy tool 1 can be enabled.');
    $this->assertFieldChecked('edit-feadmin-feadmin-dummy-second-enabled-data', 'Dummy tool 2 can be enabled.');
    $this->assertFieldById('edit-feadmin-feadmin-dummy-first-weight-data', 5, 'Order saved successfully.');

    // ----------------------------------------------------------------------
    // 6) Check saving of specific configurations.
    $this->assertNoText('Dummy tool 1: Your label must contain the word dummy.', 'Specific tool validation is passed.');
    $this->assertFieldById('edit-feadmin-dummy-first-dummy-label', 'Another dummy label', 'Dummy tool 1 has specific label configuration saved.');
    $this->assertNoFieldChecked('edit-feadmin-dummy-first-dummy-option', 'Dummy tool 1 has specific option configuration saved.');
  }
}
