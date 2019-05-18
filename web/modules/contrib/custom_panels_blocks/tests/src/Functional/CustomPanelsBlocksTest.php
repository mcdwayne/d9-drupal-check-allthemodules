<?php

namespace Drupal\Tests\custom_panels_blocks\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the default CustomPanelsBlocksTest module.
 */
class CustomPanelsBlocksTest extends BrowserTestBase {
  /**
   * User with permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'panels',
    'custom_panels_blocks',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    // Create user that will be used for tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer custom_panels_blocks',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * TestCustomPanelsBlocks test.
   */
  public function testCustomPanelsBlocks() {
    // Checks if exists link in config page.
    $this->drupalGet('admin/config');
    $this->assertSession()->responseContains('Custom panels blocks');
    // Checks if are all unchecked all checkboxes.
    $this->drupalGet('admin/config/system/custom_panels_blocks');
    $this->assertSession()->responseContains('Custom panels blocks - Settings');
    $html = $this->getSession()->getDriver()->getHtml('//*');
    $dom = new \DOMDocument();
    libxml_use_internal_errors(TRUE);
    $dom->loadHTML($html);
    $xPath = new \DOMXpath($dom);
    $domNodeList = $xPath->query("//input[@type='checkbox']/@id");
    if ($domNodeList->length > 0) {
      foreach ($domNodeList as $domElement) {
        $this->assertSession()->checkboxNotChecked($domElement->value);
      }
    }
    else {
      $this->assertFalse(TRUE, 'No inputs found.');
    }
  }

}
