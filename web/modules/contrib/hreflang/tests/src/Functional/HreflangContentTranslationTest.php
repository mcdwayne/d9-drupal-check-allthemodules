<?php

namespace Drupal\Tests\hreflang\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests for presence of the hreflang link element.
 *
 * @group hreflang
 */
class HreflangContentTranslationTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['hreflang', 'content_translation'];

  /**
   * Functional tests for the hreflang tag.
   */
  public function testHreflangTag() {
    global $base_url;
    // User to add language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'administer site configuration',
      'create page content',
    ]);
    $this->drupalLogin($admin_user);
    // Add predefined language.
    $edit = ['predefined_langcode' => 'fr'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add language');
    // Add node.
    $edit = ['title[0][value]' => 'Test front page'];
    $this->drupalPostForm('node/add/page', $edit, 'Save');
    // Set front page.
    $edit = ['site_frontpage' => '/node/1'];
    $this->drupalPostForm('admin/config/system/site-information', $edit, 'Save configuration');
    $this->drupalGet('');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/" />', 'English hreflang found on English page.');
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr" />', 'French hreflang found on English page.');
    $this->assertNoRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/node/1" />', 'English hreflang found on English page.');
    $this->assertNoRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/node/1" />', 'French hreflang found on English page.');
    $this->drupalGet('fr');
    $this->assertRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/" />', 'English hreflang found on French page.');
    $this->assertRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr" />', 'French hreflang found on French page.');
    $this->assertNoRaw('<link rel="alternate" hreflang="en" href="' . $base_url . '/node/1" />', 'English hreflang found on French page.');
    $this->assertNoRaw('<link rel="alternate" hreflang="fr" href="' . $base_url . '/fr/node/1" />', 'French hreflang found on French page.');
  }

}
