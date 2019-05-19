<?php

namespace Drupal\Tests\twitter_sync\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the block position.
 *
 * @group twitter_sync
 */
class TwitterBlockPositionTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'block_place',
    'toolbar',
    'twitter_sync',
    'twitter_sync_test_block',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['create twitter_sync content', 'edit own twitter_sync content']);
    $this->drupalLogin($web_user);

    // Test /node/add page with only one content type.
    $this->drupalGet('node/add/twitter_sync');

    // Create first node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['field_twitter_sync_status_id[0][value]'] = '1074085789603119104';
    $edit['field_twitter_sync_screen_name[0][value]'] = 's1mpleO';
    $this->drupalPostForm('node/add/twitter_sync', $edit, t('Save'));

    // Create second node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['field_twitter_sync_status_id[0][value]'] = '1074066791708680192';
    $edit['field_twitter_sync_screen_name[0][value]'] = 's1mpleO';
    $this->drupalPostForm('node/add/twitter_sync', $edit, t('Save'));

    // Create third node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['field_twitter_sync_status_id[0][value]'] = '1074004007402782725';
    $edit['field_twitter_sync_screen_name[0][value]'] = 's1mpleO';
    $this->drupalPostForm('node/add/twitter_sync', $edit, t('Save'));
  }

  /**
   * Tests placing blocks as an admin.
   */
  public function testBlockPositioned() {

    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);

    // Verify if block exists on front page.
    $twitter_block = $this->xpath(' //*[@id="block-twittersyncblock"]/div[2]/div/h2');
    $this->assert(isset($twitter_block), 'Follow us on Twitter');

  }

}
