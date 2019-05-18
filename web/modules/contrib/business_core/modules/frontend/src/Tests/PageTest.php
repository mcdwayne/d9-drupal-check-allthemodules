<?php

namespace Drupal\frontend\Tests;

/**
 * Tests page entity.
 *
 * @group page
 */
class PageTest extends FrontendTestBase {

  /**
   * Tests the list.
   */
  public function testList() {
    $this->drupalGet('admin/page');
    $this->assertResponse(200);
    $this->assertLinkByHref('admin/page/add');
  }

}
