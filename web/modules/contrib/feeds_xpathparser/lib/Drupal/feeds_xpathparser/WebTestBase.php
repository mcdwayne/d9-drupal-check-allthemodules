<?php

/**
 * @file
 * Contains Drupal\feeds_xpathparser\WebTestBase.
 */

namespace Drupal\feeds_xpathparser;

use Drupal\feeds\FeedsWebTestBase;

/**
 * Test single feeds.
 */
class WebTestBase extends FeedsWebTestBase {

  const FEEDS_BASE = 'admin/structure/feeds/manage';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'feeds_xpathparser',
  );

  /**
   * Set up test.
   */
  public function setUp() {
    parent::setUp();

    // Set the teaser length display to unlimited otherwise tests looking for
    // text on nodes will fail.
    $edit = array('fields[body][type]' => 'text_default');
    $this->drupalPost('admin/structure/types/manage/article/display/teaser', $edit, 'Save');
  }

  /**
   * Posts to a URL and checks the field values.
   *
   * @param string $url
   *   The url to POST to.
   * @param array $edit
   *   The form values.
   * @param string $button
   *   The button to press.
   * @param string $saved_text
   *   The save message text.
   */
  protected function postAndCheck($url, $edit, $button, $saved_text) {
    $this->drupalPost($url, $edit, $button);
    $this->assertText($saved_text);
    $this->drupalGet($url);
    foreach ($edit as $key => $value) {
      $this->assertFieldByName($key, $value);
    }
  }

}
