<?php

namespace Drupal\Tests\packages\Functional;

use Drupal\Tests\packages\Functional\PackagesTestBase;

/**
 * Test the Packages example modules.
 *
 * @group packages
 */
class PackagesExamplesTest extends PackagesTestBase {

  /**
   * Tests the example page module.
   */
  public function testExamplePage() {
    // The page should not be accessible by anonymous users.
    $this->drupalGet('/packages-example-page');
    $this->assertSession()->statusCodeEquals(403);

    // Log in.
    $this->drupalLogin($this->packagesExtraUser);

    // Disable the example page package.
    $this->submitPackagesForm([
      'example_page' => FALSE,
    ]);

    // The page should still not be accessible.
    $this->drupalGet('/packages-example-page');
    $this->assertSession()->statusCodeEquals(403);

    // Enable the example page package.
    $this->submitPackagesForm([
      'example_page' => TRUE,
    ]);

    // The page should be accessible now.
    $this->drupalGet('/packages-example-page');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test the example login greeting module.
   */
  public function testExampleLoginGreeting() {
    // Log in.
    $this->drupalLogin($this->packagesExtraUser);

    // Enable the example login greeting package.
    $this->submitPackagesForm([
      'login_greeting' => TRUE,
    ]);

    // Log out and log back in.
    $this->drupalLogout();
    $this->drupalLogin($this->packagesExtraUser);

    // Check for both greeting messages.
    $this->assertSession()->pageTextContains('Welcome back!');
    $this->assertSession()->pageTextContains('The current date and time is:');

    // Configure the package to not show the time and date.
    $this->drupalPostForm('/packages/login_greeting/settings', ['show_datetime' => FALSE], $this->t('Save settings'));

    // Log out and log back in.
    $this->drupalLogout();
    $this->drupalLogin($this->packagesExtraUser);

    // Check for the greeting message but no date time message.
    $this->assertSession()->pageTextContains('Welcome back!');
    $this->assertSession()->pageTextNotContains('The current date and time is:');

    // Disable the example login greeting package.
    $this->submitPackagesForm([
      'login_greeting' => FALSE,
    ]);

    // Log out and log back in.
    $this->drupalLogout();
    $this->drupalLogin($this->packagesExtraUser);

    // Check for no greeting messages.
    $this->assertSession()->pageTextNotContains('Welcome back!');
    $this->assertSession()->pageTextNotContains('The current date and time is:');
  }

}
