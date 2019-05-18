<?php

/**
 * @file
 * Definition of Drupal\naming\Tests\NamingTest.
 */

namespace Drupal\naming\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests naming conventions.
 *
 * @group Naming
 */
class NamingTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'node', 'help', 'block', 'naming'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('help_block');

    $admin_user = $this->drupalCreateUser(['administer content types', 'administer naming conventions', 'access administration pages']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests naming conventions.
   */
  public function testNamingConventions() {
    // Check 'Add content type' page.
    $this->drupalGet('/admin/structure/types/add');
    $this->assertRaw('<summary role="button" aria-expanded="false" aria-pressed="false">Content type naming conventions</summary>');
    $this->assertRaw('&lt;p&gt;Recommendations&lt;/p&gt;');

    // Check 'machine name' widget is disabled (by default).
    $this->drupalGet('/admin/structure/types/add');
    $this->assertNoRaw('/core/misc/machine-name.js');

    // Check 'machine name' widget is enabled.
    $this->drupalPostForm('/admin/config/development/naming/settings', ['disable_machine_name' => FALSE], t('Save configuration'));
    $this->drupalGet('/admin/structure/types/add');
    $this->assertRaw('/core/misc/machine-name.js');

    // Check main help page.
    $this->drupalGet('/admin/help/naming');
    $this->assertRaw('<div id="introduction" class="naming-category">');
    $this->assertRaw('<h2>Introduction</h2>');
    $this->assertRaw('<em>Applies to: <a href="' . base_path() . 'admin/structure/types">/admin/structure/types</a></em>');
  }

}
