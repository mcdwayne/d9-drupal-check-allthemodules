<?php

namespace Drupal\Tests\ajax_assets_plus\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests assets plus response.
 */
class AssetsPlusResponseTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_assets_plus_example'];

  /**
   * Tests response with libraries load.
   */
  public function testLibrariesLoad() {
    $this->drupalGet('/ajax-assets-plus-example/date', ['query' => ['_format' => 'json']]);
    $this->assertSession()->responseContains('"libraries"');
    $this->assertSession()->responseContains('"ajax_assets_plus_example\/time');
  }

}
