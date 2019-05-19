<?php

namespace Drupal\Tests\twitter_sync\Functional;

/**
 * Test config page save.
 *
 * @group twitter_sync
 */
class TwitterSyncConfigPageSaveFormTest extends TwitterTestBase {

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test fill admin form.
   */
  public function testAccessFormWithAdminUser() {
    // Test /admin/config/system/twitter_sync
    // page with only one content type.
    $this->drupalGet('admin/config/system/twitter_sync');
    $this->assertResponse(200);
    $this->assertUrl('admin/config/system/twitter_sync');

  }

  /**
   * Test fill admin form.
   */
  public function testAccessFormWithNonAdminUser() {
    // Logout admin.
    $this->drupalLogout();

    // Test admin/config/system/twitter_sync
    // page with only one content type.
    $this->drupalGet('admin/config/system/twitter_sync');
    $this->assertResponse(403);
    $this->assertUrl('admin/config/system/twitter_sync');

  }

  /**
   * Test fill admin form.
   */
  public function testFillForm() {
    // Test /admin/config/system/twitter_sync
    // page with only one content type.
    $this->drupalGet('admin/config/system/twitter_sync');
    $this->assertResponse(200);

    // Fill the form.
    $edit = [];

    $value1 = $this->randomMachineName(16);
    $value2 = $this->randomMachineName(16);
    $value3 = $this->randomMachineName(16);
    $value4 = $this->randomMachineName(16);

    $edit['field_twitter_sync_consumer_key'] = $value1;
    $edit['field_twitter_sync_consumer_secret'] = $value2;
    $edit['field_twitter_sync_access_token'] = $value3;
    $edit['field_twitter_sync_access_token_secret'] = $value4;
    $edit['field_twitter_screen_name'] = 's1mpleO';

    $this->drupalPostForm('admin/config/system/twitter_sync', $edit, t('Save configuration'));

    // Verify that the creation message contains a link to a node.
    $config_saved_msg = $this->xpath('//*[@id="edit-field-twitter-sync-consumer-key"]');
    $this->assert(isset($config_saved_msg), $value1);

    $config_saved_msg = $this->xpath('//*[@id="edit-field-twitter-sync-consumer-secret"]');
    $this->assert(isset($config_saved_msg), $value2);

    $config_saved_msg = $this->xpath('//*[@id="edit-field-twitter-sync-access-token"]');
    $this->assert(isset($config_saved_msg), $value3);

    $config_saved_msg = $this->xpath('//*[@id="edit-field-twitter-sync-access-token-secret"]');
    $this->assert(isset($config_saved_msg), $value4);

    $config_saved_msg = $this->xpath('//*[@id="edit-field-twitter-screen-name"]');
    $this->assert(isset($config_saved_msg), 's1mpleO');
  }

}
