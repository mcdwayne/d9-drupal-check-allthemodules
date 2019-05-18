<?php

namespace Drupal\Tests\hn\Functional\UI;

use Drupal\Tests\hn\Functional\HnFunctionalTestBase;

/**
 * This tests the config UI of the hn_cleaner module.
 *
 * @group hn
 */
class HnConfigUITest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_config',
  ];

  /**
   * Tries opening the settings page without sufficient permissions.
   */
  public function testPermissions() {
    $this->drupalGet('admin/config/services/hn/config');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * This checks if the config entities are validated on existence.
   */
  public function testConfigEntityValidation() {
    // Fetch the settings page with sufficient permissions.
    $account = $this->drupalCreateUser(['administer hn']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/services/hn/config');

    // Save the config before submit.
    $before_submit = $this->config('hn_config.settings')->get('entities');

    // Submit with an invalid configuration entity.
    $this->submitForm([
      'entities' => 'hn_config.settings' . PHP_EOL . 'doesnot.exist',
    ], t('Save configuration'));

    // Make sure the message is shown.
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Config entity doesnot.exist does not exist');

    // Assure the config wasn't changed.
    $this->assertEquals($before_submit, $this->config('hn_config.settings')->get('entities'));
  }

  /**
   * This tests assures that changing the settings actually work.
   */
  public function testChangingSettings() {
    // Fetch the settings page with sufficient permissions.
    $account = $this->drupalCreateUser(['administer hn']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/services/hn/config');

    // Make sure all the default values are visible.
    $assert_session = $this->assertSession();
    $this->assertEquals(
      ['main'],
      $assert_session->fieldExists('menus[]')->getValue()
    );
    $assert_session->fieldValueEquals('entities', '');

    // Change the form values and submit.
    $this->submitForm([
      'menus[]' => ['footer', 'main'],
      'entities' => 'hn_config.settings' . PHP_EOL . 'hn.settings',
    ], t('Save configuration'));

    // Assure the changed values are visible after submit.
    $assert_session = $this->assertSession();
    $this->assertEquals(
      ['footer', 'main'],
      $assert_session->fieldExists('menus[]')->getValue()
    );
    $assert_session->fieldValueEquals('entities', 'hn_config.settings' . PHP_EOL . 'hn.settings');

    // Assure the config was actually changed.
    $config = $this->config('hn_config.settings');
    $this->assertEquals(['footer', 'main'], $config->get('menus'));
    $this->assertEquals(['hn_config.settings', 'hn.settings'], $config->get('entities'));
  }

}
