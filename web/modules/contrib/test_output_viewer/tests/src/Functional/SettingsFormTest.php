<?php

namespace Drupal\Tests\test_output_viewer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test for settings form.
 *
 * @group test_output_viewer
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['test_output_viewer'];

  /**
   * Test callback.
   */
  public function testSettingsForm() {
    $admin_user = $this->drupalCreateUser(['administer test output viewer']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/development/test-output/settings');

    $this->assertXpath('//h1[text() = "Settings"]');
    $xpath = '//label[text() = "Relative path to test output directory"]';
    $xpath .= '/following-sibling::input[@name = "output_path" and @value = "sites/simpletest/browser_output"]';
    $this->assertXpath($xpath);
    $xpath = '//input[@name = "default_result" and @value = "first" and @checked = "checked"]';
    $xpath .= '/following-sibling::label[text() = "First"]';
    $this->assertXpath($xpath);
    $xpath = '//input[@name = "auto_update" and @checked = "checked"]';
    $xpath .= '/following-sibling::label[text() = "Auto update"]';
    $this->assertXpath($xpath);
    $xpath = '//label[text() = "Auto update timeout"]';
    $xpath .= '/following-sibling::input[@name = "auto_update_timeout" and @value = "1.5"]';
    $this->assertXpath($xpath);

    $edit = [
      'output_path' => 'example',
      'default_result' => 'last',
      'auto_update' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $xpath = '//input[@name = "output_path" and @value = "example"]';
    $this->assertXpath($xpath);
    $xpath = '//input[@name = "default_result" and @value = "last" and @checked = "checked"]';
    $this->assertXpath($xpath);
    $xpath = '//input[@name = "auto_update" and not(@checked)]';
    $this->assertXpath($xpath);
  }

  /**
   * Checks that an element exists on the current page.
   *
   * @param string $selector
   *   The XPath identifying the element to check.
   */
  protected function assertXpath($selector) {
    $this->assertSession()->elementExists('xpath', $selector);
  }

}
