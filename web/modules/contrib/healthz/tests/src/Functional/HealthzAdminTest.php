<?php

namespace Drupal\Tests\healthz\Functional;

/**
 * Functional tests for the admin form.
 *
 * @group healthz
 */
class HealthzAdminTest extends FunctionalTestBase {

  /**
   * Tests the admin form functionality.
   */
  public function testAdminForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/system/healthz');
    // Ensure the form is correctly displaying module defaults with values from
    // both config and new plugins that aren't yet configured.
    $this->assertCheckboxChecked('checks[file_system][status]');
    $this->assertCheckboxNotChecked('checks[passing_check][status]');

    $this->assertSession()->fieldNotExists('checks[does_not_apply][status]');

    $edit = [
      "checks[passing_check][status]" => TRUE,
      "checks[passing_check][weight]" => -10,
      "checks[passing_check][settings][test_setting]" => TRUE,
      "checks[failing_200][status]" => TRUE,
      "checks[failing_200][weight]" => -5,
      "checks[failing_check][status]" => FALSE,
      "checks[failing_check][weight]" => 0,
    ];
    $this->drupalPostForm('admin/config/system/healthz', $edit, 'Save configuration');

    $this->assertCheckboxChecked('checks[passing_check][status]');
    $this->assertCheckboxChecked('checks[passing_check][settings][test_setting]');
  }

}
