<?php

namespace Drupal\antisearch_filter\Tests;

/**
 * The antisearch filter acts as a field formatter.
 *
 * @group antisearch_filter
 */
class FieldFormatterTest extends AntisearchWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'antisearch_filter'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->user = $this->drupalCreateUser([
      'administer filters',
      'create page content',
      'administer content types',
      'administer node fields',
      'administer node display',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Field formatter.
   */
  public function testFieldFormatter() {
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertText(t('Antisearch filter formatter'));
    $edit = [
      'fields[body][type]' => 'antisearch_filter_formatter',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = 'josef@friedrich.rocks';
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertRaw('<span class="antisearch-filter"');

    // Check if CSS is loaded.
    $this->assertRaw('antisearch_filter/antisearch_filter.css');
  }

}
