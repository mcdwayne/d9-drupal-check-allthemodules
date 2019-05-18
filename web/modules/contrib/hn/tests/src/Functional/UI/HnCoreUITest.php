<?php

namespace Drupal\Tests\hn\Functional\UI;

use Drupal\Tests\hn\Functional\HnFunctionalTestBase;

/**
 * This tests the config UI of the hn module.
 *
 * @group hn
 */
class HnCoreUITest extends HnFunctionalTestBase {

  /**
   * Tries opening the settings page without sufficient permissions.
   */
  public function testPermissions() {
    $this->drupalGet('admin/config/services/hn');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * This tests assures that the 'enable cache' checkbox works.
   */
  public function testCacheCheckbox() {
    // The default cache setting should be TRUE.
    $this->assertEquals(TRUE, $this->config('hn.settings')->get('cache'));

    // Fetch the settings page with sufficient permissions.
    $account = $this->drupalCreateUser(['administer hn']);
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/services/hn');

    // Make sure the checkbox is ticked.
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals('cache', TRUE);

    // Untick the checkbox and save configuration.
    $this->submitForm(['cache' => FALSE], t('Save configuration'));

    // Make sure the checkbox is unticked.
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals('cache', FALSE);

    // Assure the config was actually changed.
    $this->assertEquals(FALSE, $this->config('hn.settings')->get('cache'));
  }

}
