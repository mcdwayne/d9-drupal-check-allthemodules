<?php

namespace Drupal\Tests\ajax_assets_plus\Functional;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests assets plus javascript.
 */
class AssetsPlusJavascriptTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ajax_assets_plus_example'];

  /**
   * Tests libraries load in ajax request.
   */
  public function testLibrariesLoadInAjaxRequest() {
    $this->drupalGet('/ajax-assets-plus-example/date-page');
    $link = $this->getSession()->getPage()->findLink('Get date');
    $this->assertSession()->pageTextNotContains('Current date:');
    $this->assertSession()->pageTextNotContains('Current time:');
    $link->click();
    $this->assertSession()->waitForElement('css', '.ajax-assets-plus-example-date__time', 100);
    $this->assertSession()->pageTextContains('Current date:');
    $this->assertSession()->pageTextContains('Current time:');
  }

}
