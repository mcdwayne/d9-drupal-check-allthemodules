<?php

namespace Drupal\Tests\applenews\Functional;

/**
 * Tests channel administration page functionality.
 *
 * @group applenews
 */
class ApplenewsChannelAdminTest extends ApplenewsTestBase {

  /**
   * Tests template pages.
   */
  public function testAppleNewsTemplateAdminPages() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    // Verify overview page has empty message by default.
    $this->drupalGet('admin/config/services/applenews/channel');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('There are no applenews channel entities yet.');

    $assert_session->linkExists('Add Apple News Channel');
  }

}
