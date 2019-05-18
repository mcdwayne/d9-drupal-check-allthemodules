<?php

namespace Drupal\Tests\layout_builder_modal\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of layout_builder_modal module.
 *
 * @group layout_builder_modal
 */
class LayoutBuilderModalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder', 'layout_builder_modal'];

  /**
   * Tests the Layout Builder Modal settings form.
   */
  public function testSettingsForm() {
    $assert_session = $this->assertSession();

    // Test access is denied for user without administer permission.
    $account = $this->drupalCreateUser([]);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/user-interface/layout-builder-modal');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogout();

    // Test access is allowed for user with administer permission.
    // Test configuration forms submits correctly.
    $account = $this->drupalCreateUser(['administer layout builder modal']);
    $this->drupalLogin($account);

    $edit = [
      'modal_width' => 800,
      'modal_height' => 500,
    ];

    $this->drupalGet('admin/config/user-interface/layout-builder-modal');
    $assert_session->statusCodeEquals(200);
    $this->submitForm($edit, 'Save configuration');

    $settings = $this->config('layout_builder_modal.settings');

    $this->assertEquals($settings->get('modal_width'), 800);
    $this->assertEquals($settings->get('modal_height'), 500);

    $edit = [
      'modal_height' => 'auto',
    ];

    $this->submitForm($edit, 'Save configuration');

    $settings = $this->config('layout_builder_modal.settings');

    $this->assertEquals($settings->get('modal_height'), 'auto');
  }

}
