<?php

namespace Drupal\Tests\module_sitemap\Functional;

use Drupal\Core\Url;

/**
 * Tests to make sure the Module Sitemap link is visible.
 *
 * @package Drupal\Tests\module_sitemap\Functional
 */
class LinkVisibilityTest extends FunctionalTestBase {

  /**
   * Tests to see if the "Module Sitemap" link is visible.
   */
  public function testLinkVisibility() {
    $this->drupalGet(Url::fromRoute('module_sitemap.module-sitemap')->toString());
    $this->assertSession()->linkExists('Module Sitemap');
  }

}
