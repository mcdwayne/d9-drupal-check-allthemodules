<?php

namespace Drupal\twitter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Twitter module functionality.
 *
 * @group Twitter
 */
class TwitterCore extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twitter', 'views'];

  /**
   * Tests account addition without Oauth module activated.
   */
  public function testAccountAdditionNoOauth() {
    // Create user.
    $twitter_user = $this->drupalCreateUser([
      'add twitter accounts',
      'import own tweets',
    ]);
    $this->drupalLogin($twitter_user);

    // Add a Twitter account.
    $edit = [
      'screen_name' => 'drupal',
    ];
    $this->drupalPost('user/' . $this->user->uid . '/edit/twitter',
                      $edit, t('Add account'));
    $this->assertLink('drupal', 0,
      t('Twitter account was added successfully'));

    // Load tweets.
    twitter_cron();
    $this->drupalGet('user/' . $this->user->uid . '/tweets');
    $elements = $this->xpath('//div[contains(@class, "view-tweets")]/div/table');
    $this->assertTrue(count($elements), 'Tweets were loaded successfully.');
    // Delete the Twitter account.
    $edit = [
      'accounts[0][delete]' => 1,
    ];
    $this->drupalPost('user/' . $this->user->uid . '/edit/twitter',
                      $edit, t('Save changes'));
    $this->assertText(t('The Twitter account was deleted.'));
  }

}
