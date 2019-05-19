<?php

namespace Drupal\site_map\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test case class for site map css file tests.
 *
 * @group site_map
 */
class SiteMapCssTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('site_map', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access site map',
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests include css file.
   */
  public function testIncludeCssFile() {
    // Assert that css file is included by default.
    $this->drupalGet('/sitemap');
    $this->assertRaw('site_map.theme.css');

    // Change module not to include css file.
    $edit = array(
      'css' => TRUE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Clearing the cache is needed for the test.
    $this->drupalPostForm('admin/config/development/performance', array(), 'Clear all caches');

    // Assert that css file is not included.
    $this->drupalGet('/sitemap');
    $this->assertNoRaw('site_map.theme.css');
  }
}
