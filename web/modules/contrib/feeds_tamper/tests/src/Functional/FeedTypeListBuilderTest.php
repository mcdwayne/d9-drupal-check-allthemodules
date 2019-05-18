<?php

namespace Drupal\Tests\feeds_tamper\Functional;

/**
 * Tests that the Tamper link is shown on the feed type list page.
 *
 * @group feeds_tamper
 */
class FeedTypeListBuilderTest extends FeedsTamperBrowserTestBase {

  /**
   * Tests that the tamper operation is displayed on the feed type list page.
   */
  public function testUiWithRestrictedPrivileges() {
    // Add two feed types.
    $this->feedType = $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
    ]);
    $this->feedType = $this->createFeedType([
      'id' => 'my_feed_type_restricted',
      'label' => 'My feed type (restricted)',
    ]);

    // Add an user who may only tamper 'my_feed_type'.
    $account = $this->drupalCreateUser([
      'administer feeds',
      'tamper my_feed_type',
    ]);
    $this->drupalLogin($account);

    // Assert that the tamper operation links is being displayed only for
    // my_feed_type .
    $this->drupalGet('/admin/structure/feeds');
    $session = $this->assertSession();

    $session->linkExists('Tamper');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/tamper');
    $session->linkByHrefNotExists('/admin/structure/feeds/manage/my_feed_type_restricted/tamper');
  }

}
