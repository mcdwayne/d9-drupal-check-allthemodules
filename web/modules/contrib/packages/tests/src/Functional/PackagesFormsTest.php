<?php

namespace Drupal\Tests\packages\Functional;

use Drupal\Tests\packages\Functional\PackagesTestBase;

/**
 * Test the Packages and Package settings form.
 *
 * @group packages
 */
class PackagesFormsTest extends PackagesTestBase {

  /**
   * Tests the Packages form.
   */
  public function testPackagesForm() {
    // The package form should not be accessible to anonymous users.
    $this->drupalGet('/packages');
    $this->assertSession()->statusCodeEquals(403);

    // Log in as a user with just the primary packages permission.
    $this->drupalLogin($this->packagesUser);

    // The package form should be accessible to authorized users.
    $this->drupalGet('/packages');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', '#packages-form');

    // We should see only one of the plugins because one requires
    // another permission.
    $this->assertSession()->pageTextContains('Package example: Login greeting');
    $this->assertSession()->pageTextNotContains('Package example: Page');

    // Log in with primary packages permission plus the custom
    // package permission.
    $this->drupalLogin($this->packagesExtraUser);

    // Check that the additional package is now present.
    $this->drupalGet('/packages');
    $this->assertSession()->pageTextContains('Package example: Page');

    // Since no states have been saved yet, only the login greeting
    // package should be enabled since that is the default.
    $this->assertSession()->checkboxNotChecked('edit-packages-example-page-enabled');
    $this->assertSession()->checkboxChecked('edit-packages-login-greeting-enabled');

    // Only the login greeting package should have a settings link.
    $this->assertSession()->elementExists('css', '#edit-packages-login-greeting-settings');
    $this->assertSession()->elementNotExists('css', '#edit-packages-example-page-settings');

    // Test packages are in alphabetical order.
    $query = "//table[@id='packages-list']/tbody/tr/td[contains(@class, 'package')]/h3";
    $results = $this->xpath($query);
    $this->assertEquals('Package example: Login greeting', $results[0]->getText());
    $this->assertEquals('Package example: Page', $results[1]->getText());

    // Swap the package status and submit the form.
    $this->submitPackagesForm([
      'example_page' => TRUE,
      'login_greeting' => FALSE,
    ]);

    // Check that the packages have been toggled.
    $this->assertSession()->checkboxChecked('edit-packages-example-page-enabled');
    $this->assertSession()->checkboxNotChecked('edit-packages-login-greeting-enabled');

    // Now that login greeting is disabled, make sure the settings link is gone.
    $this->assertSession()->elementNotExists('css', '#edit-packages-login-greeting-settings');
  }

  /**
   * Test the Package settings form.
   */
  public function testPackageSettingsForm() {
    // Settings should not be available if we're not logged in.
    $this->drupalGet('/packages/login_greeting/settings');
    $this->assertSession()->statusCodeEquals(403);

    // Log in with primary packages permission plus the custom
    // package permission.
    $this->drupalLogin($this->packagesExtraUser);

    // Disable the login greeting package and make sure no access still.
    $this->submitPackagesForm(['login_greeting' => FALSE]);
    $this->drupalGet('/packages/login_greeting/settings');
    $this->assertSession()->statusCodeEquals(403);

    // Enable the package.
    $this->submitPackagesForm(['login_greeting' => TRUE]);

    // Access the settings form for the login greeting package by going through
    // the packages form.
    $this->drupalGet('/packages');
    $this->clickLink($this->t('Settings'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Include the date and time in your greeting');
    $this->assertSession()->elementExists('css', '#edit-submit');
    $this->assertSession()->addressEquals('packages/login_greeting/settings');

    // Validate the default settings.
    $this->assertSession()->checkboxChecked('edit-show-datetime');

    // Uncheck the datetime setting and save the form.
    $this->drupalPostForm(NULL, ['show_datetime' => FALSE], $this->t('Save settings'));

    // Validate redirect to packages form and success message.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The settings have been saved.');
    $this->assertSession()->addressEquals('packages');

    // Reload form and test that settings are changed.
    $this->clickLink($this->t('Settings'));
    $this->assertSession()->checkboxNotChecked('edit-show-datetime');

    // Test that the cancel link returns to the main form.
    $this->clickLink($this->t('Cancel'));
    $this->assertSession()->addressEquals('packages');

    // Return to the package settings form.
    $this->clickLink($this->t('Settings'));

    // Disable the package from the settings form.
    $this->drupalPostForm(NULL, [], $this->t('Disable'));

    // We should have been redirected and given a message.
    $this->assertSession()->addressEquals('packages');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The package has been disabled.');

    // Verify that the checkbox is unchecked now.
    $this->assertSession()->checkboxNotChecked('edit-packages-login-greeting-enabled');
  }

}
