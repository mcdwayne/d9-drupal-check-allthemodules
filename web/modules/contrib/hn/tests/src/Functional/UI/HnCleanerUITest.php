<?php

namespace Drupal\Tests\hn\Functional\UI;

use Drupal\Tests\hn\Functional\HnFunctionalTestBase;

/**
 * This tests the config UI of the hn_cleaner module.
 *
 * @group hn
 */
class HnCleanerUITest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_cleaner',
  ];

  /**
   * Tries opening the settings page without sufficient permissions.
   */
  public function testPermissions() {
    $this->drupalGet('admin/config/services/hn/cleaner');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * This tests assures that the disable checkboxes work.
   */
  public function testCheckboxes() {
    // Fetch the settings page with sufficient permissions.
    $account = $this->drupalCreateUser(['administer hn']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/services/hn/cleaner');

    // Make sure all checkboxes are un-ticked.
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals("disable_entity_view", FALSE);
    $assert_session->fieldValueEquals("disable_entity_user", FALSE);
    $assert_session->fieldValueEquals("disable_entity_user_field_uid", FALSE);
    $assert_session->fieldValueEquals("disable_entity_user_field_uuid", FALSE);

    // Untick the 'view' and 'uid' checkbox and save configuration.
    $this->submitForm([
      'disable_entity_view' => TRUE,
      'disable_entity_user_field_uid' => TRUE,
    ], t('Save configuration'));

    // Make sure these checkboxes are now checked (and others aren't).
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals("disable_entity_view", TRUE);
    $assert_session->fieldValueEquals("disable_entity_user", FALSE);
    $assert_session->fieldValueEquals('disable_entity_user_field_uid', TRUE);
    $assert_session->fieldValueEquals('disable_entity_user_field_uuid', FALSE);

    // Assure the config was actually changed.
    $config = $this->config('hn_cleaner.settings');
    $this->assertEquals(['view'], $config->get('entities'));
    $this->assertEquals(['user' => ['uid']], $config->get('fields'));
  }

}
