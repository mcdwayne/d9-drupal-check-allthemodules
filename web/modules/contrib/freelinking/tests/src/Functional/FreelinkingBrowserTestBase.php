<?php

namespace Drupal\Tests\freelinking\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base test class for functional tests.
 */
abstract class FreelinkingBrowserTestBase extends BrowserTestBase {

  public static $modules = [
    'node',
    'user',
    'file',
    'filter',
    'search',
    'freelinking',
  ];

  /**
   * A privileged user account to test with.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $privilegedUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a content type.
    $this->createContentType(['name' => 'Basic page', 'type' => 'page']);

    $this->privilegedUser = $this->createUser([
      'access administration pages',
      'access content',
      'administer content types',
      'administer filters',
      'access user profiles',
      'create page content',
      'edit own page content',
    ]);
    $this->drupalLogin($this->privilegedUser);

    // Create two nodes as the very basic requirements for freelinking.
    $this->drupalCreateNode(['type' => 'page', 'title' => t('First page')]);
    $this->drupalCreateNode(['type' => 'page', 'title' => t('Second page')]);
  }

  /**
   * Update filter settings.
   *
   * @param string $name
   *   The filter name to edit. Defaults to 'plain_text'.
   * @param array $edit
   *   The Freelinking filter configuration to edit. Defaults to all freelinking
   *   plugins enabled.
   */
  protected function updateFilterSettings($name = 'plain_text', array $edit = NULL) {
    $label = str_replace('_', ' ', ucwords($name));

    // Set default edit options.
    if (!isset($edit)) {
      $edit = [
        'filters[freelinking][status]' => 1,
        'filters[freelinking][weight]' => 0,
        'filters[freelinking][settings][plugins][nodetitle][enabled]' => 1,
        'filters[freelinking][settings][plugins][external][enabled]' => 1,
        'filters[freelinking][settings][plugins][external][settings][scrape]' => 0,
        'filters[freelinking][settings][plugins][file][enabled]' => 1,
        'filters[freelinking][settings][plugins][file][settings][scheme]' => 'public',
        'filters[freelinking][settings][plugins][drupalorg][enabled]' => 1,
        'filters[freelinking][settings][plugins][drupalorg][settings][scrape]' => 0,
        'filters[freelinking][settings][plugins][drupalorg][settings][node]' => 1,
        'filters[freelinking][settings][plugins][drupalorg][settings][project]' => 1,
        'filters[freelinking][settings][plugins][google][enabled]' => 1,
        'filters[freelinking][settings][plugins][nid][enabled]' => 1,
        'filters[freelinking][settings][plugins][path_alias][enabled]' => 1,
        'filters[freelinking][settings][plugins][search][enabled]' => 1,
        'filters[freelinking][settings][plugins][user][enabled]' => 1,
        'filters[freelinking][settings][plugins][wiki][enabled]' => 1,
        'filters[filter_url][weight]' => 1,
        'filters[filter_html][weight]' => 2,
        'filters[filter_autop][weight]' => 3,
        'filters[filter_htmlcorrector][weight]' => 4,
      ];
    }

    $this->drupalPostForm('/admin/config/content/formats/manage/' . $name, $edit, t('Save configuration'));
    $this->assertText(t('The text format @label has been updated.', ['@label' => $label]));
    $this->drupalGet('admin/config/content/formats/manage/' . $name);
    $this->assertFieldChecked('edit-filters-freelinking-status');
  }

}
