<?php

namespace Drupal\Tests\twitter_sync\Functional;

/**
 * Create a tweet node and test saving it.
 *
 * @group twitter_sync
 */
class CreateTwitterContentTest extends TwitterTestBase {

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['create twitter_sync content', 'edit own twitter_sync content']);
    $this->drupalLogin($web_user);
  }

  /**
   * Unit Test Twitter Content.
   */
  public function testTwitterCreateContent() {
    $node_type_storage = \Drupal::entityManager()->getStorage('node_type');

    // Test /node/add page with only one content type.
    $this->drupalGet('node/add/twitter_sync');
    $this->assertResponse(200);

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['field_twitter_sync_status_id[0][value]'] = '1073974863549878277';
    $edit['field_twitter_sync_screen_name[0][value]'] = 's1mpleO';
    $this->drupalPostForm('node/add/twitter_sync', $edit, t('Save'));

    // Check that the Basic page has been created.
    $this->assertText(t('@post @title has been created.', ['@post' => 'Twitter Sync', '@title' => $edit['title[0][value]']]), 'Twitter Sync created.');

    // Verify that the creation message contains a link to a node.
    $view_link = $this->xpath('//div[@class="messages"]//a[contains(@href, :href)]', [':href' => 'node/']);
    $this->assert(isset($view_link), 'The message area contains a link to a node');

    // Clean node_type.
    $node_type_storage->load('twitter_sync')->delete();
  }

  /**
   * Unit Test Twitter Failed Content.
   */
  public function testFailureTwitterCreateContent() {
    // Test /node/add page with only one content type.
    $this->drupalGet('node/add/twitter_sync');
    $this->assertResponse(200);

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);

    // As field field_twitter_sync_status_id is mandatory, so should show error.
    $this->drupalPostForm('node/add/twitter_sync', $edit, t('Save'));

    // We should stay in the same page.
    $this->assertUrl('node/add/twitter_sync');
  }

}
