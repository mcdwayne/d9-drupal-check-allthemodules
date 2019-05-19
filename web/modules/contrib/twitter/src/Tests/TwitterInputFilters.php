<?php

namespace Drupal\twitter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class TwitterInputFilters.
 */
class TwitterInputFilters extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'node', 'twitter'];

  /**
   * Tests input filters.
   */
  public function testInputFilters() {
    // Create user.
    $privileged_user = $this->drupalCreateUser([
      'bypass node access',
      'administer filters',
    ]);
    $this->drupalLogin($privileged_user);

    // Activate twitter input filters.
    $edit = [
      'filters[twitter_username][status]' => 1,
      'filters[twitter_username][weight]' => 0,
      'filters[twitter_hashtag][status]' => 1,
      'filters[twitter_hashtag][weight]' => 1,
      'filters[filter_url][weight]' => 2,
      'filters[filter_html][weight]' => 3,
      'filters[filter_autop][weight]' => 4,
      'filters[filter_htmlcorrector][weight]' => 5,
    ];
    $this->drupalPost('admin/config/content/formats/filtered_html', $edit, t('Save configuration'));
    $this->assertText(t('The text format Filtered HTML has been updated.'));
    $this->drupalGet('admin/config/content/formats/filtered_html');
    $this->assertFieldChecked('edit-filters-twitter-username-status',
                              'Twitter username input filter has been activated');
    $this->assertFieldChecked('edit-filters-twitter-hashtag-status',
                              'Twitter hashtag input filter has been activated');

    // Create a page so we can evaluate the filters.
    $search = '#drupal';
    $username = '@drupal';
    $edit = [];
    $edit['title'] = 'Test page';
    $edit['body[und][0][value]'] = 'This is a search over #drupal tag. There is also a link ' .
      ' to a Twitter account here: @drupal.';
    $this->drupalPost('node/add/page', $edit, t('Save'));
    $this->assertText(t('Basic page @title has been created.', ['@title' => $edit['title']]));
    $this->assertLink($search, 0, 'Twitter search input filter was created successfully.');
    $this->assertLink($username, 0, 'Twitter username input filter was created successfully.');
  }

}
