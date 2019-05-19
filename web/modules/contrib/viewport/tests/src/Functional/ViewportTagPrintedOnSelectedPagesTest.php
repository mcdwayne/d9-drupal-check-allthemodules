<?php

namespace Drupal\Tests\viewport\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Viewport tag is applied on selected pages with the right values.
 *
 * @group viewport
 */
class ViewportTagPrintedOnSelectedPagesTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['viewport'];

  /**
   * User with 'administer viewport' permission to be used on tests.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Sets up the required environment for the tests.
   */
  public function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('administer viewport'));
  }

  /**
   * Tests that the custom viewport tag is displayed as configured.
   */
  public function testViewportTagIsPrintedWithTheRightValues() {
    // Log admin user in.
    $this->drupalLogin($this->adminUser);

    // Set values to be submitted on the viewport settings page.
    $edit = array();
    $edit['width'] = '980';
    $edit['height'] = '1000';
    $edit['initial_scale'] = '1.3';
    $edit['minimum_scale'] = '0.25';
    $edit['maximum_scale'] = '5.0';
    $edit['user_scalable'] = TRUE;
    // Target all node pages.
    $edit['selected_pages'] = "/node/*";

    // Store viewport settings.
    $this->drupalGet('admin/appearance/settings/viewport');
    $this->submitForm($edit, t('Save configuration'));

    // Visit front page and check the viewport presence and values.
    $this->drupalGet('/node/');

    // Assert viewport tag is present with the right values.
    $custom_viewport = 'meta name="viewport" content="width=980, height=1000, initial-scale=1.3, minimum-scale=0.25, maximum-scale=5, user-scalable=yes"';
    $this->assertSession()->responseContains($custom_viewport);
  }

  /**
   * Tests that a custom viewport is not displayed on pages not selected.
   */
  public function testViewportTagNotPrintedInPagesNotSelected() {
    // Log admin user in.
    $this->drupalLogin($this->adminUser);

    // Set values to be submitted on the viewport settings page.
    $edit = array();
    $edit['width'] = '980';
    $edit['height'] = '1000';
    $edit['initial_scale'] = '1.3';
    $edit['minimum_scale'] = '0.25';
    $edit['maximum_scale'] = '5.0';
    $edit['user_scalable'] = TRUE;
    // Target all node pages.
    $edit['selected_pages'] = '/node/*';

    // Store viewport settings.
    $this->drupalGet('admin/appearance/settings/viewport');
    $this->submitForm($edit, t('Save configuration'));

    // Visit a page not configured with custom viewport, and check viewport.
    $this->drupalGet('admin');
    $core_viewport = 'meta name="viewport" content="width=device-width, initial-scale=1.0"';
    $this->assertSession()->responseContains($core_viewport);
    $custom_viewport = 'meta name="viewport" content="width=980, height=1000, initial-scale=1.3, minimum-scale=0.25, maximum-scale=5, user-scalable=yes"';
    $this->assertSession()->responseNotContains($custom_viewport);
  }

}
