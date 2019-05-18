<?php

namespace Drupal\apptiles\Tests;

/**
 * Testing the "Application Tiles" module.
 *
 * @group apptiles
 */
class ApplicationTilesTest extends ApplicationTilesTestBase {

  /**
   * Testing correctness of generated "browserconfig.xml".
   *
   * @param array $settings
   *   Configurations for browserconfig.xml.
   */
  public function testBrowserConfig(array $settings = [
    'msapplication' => [
      'tile' => [
        'TileColor' => '#333',
      ],
      'notification' => [
        'cycle' => 4,
        'frequency' => 20,
        'polling-uri' => ['src' => '/public-rss.xml'],
        'polling-uri1' => ['src' => '/other-rss.xml'],
      ],
    ],
  ]) {
    // Clear cache, thereby override/create a "browserconfig.xml".
    $this->resetAll();

    $this->assertTrue(file_exists(APPTILES_BROWSERCONFIG), sprintf('File "%s" exists.', APPTILES_BROWSERCONFIG));
    $this->recursiveSettingsAssertion($settings, simplexml_load_file(APPTILES_BROWSERCONFIG));
  }

  /**
   * Testing meta tags on the homepage.
   */
  public function testMetaTags() {
    $this->drupalGet('<front>');
    $this->checkMetatags();
  }

  /**
   * Testing meta tags on admin page.
   *
   * @param bool $exists
   *   Initial state of metatags existence for admin UI.
   *
   * @throws \Exception
   */
  public function testAdminPage($exists = FALSE) {
    $this->drupalLogin($this->drupalCreateUser(['administer themes']));
    // Go to theme settings page.
    $this->drupalGet('admin/appearance/settings');
    // Make sure we don't get a 403 code.
    $this->assertSession()->statusCodeEquals(200);
    // Check metatags existence on the page.
    $this->checkMetatags($exists);
    // Set option which allows to output metatags on admin pages.
    $this->drupalPostForm(NULL, [sprintf('%s[allowed_for_admin_theme]', APPTILES_MODULE_NAME) => (int) !$exists], t('Save configuration'));
    // Now state must be changed.
    $this->checkMetatags(!$exists);
  }

}
