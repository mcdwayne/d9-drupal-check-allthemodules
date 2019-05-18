<?php

namespace Drupal\no_nbsp\Tests;

/**
 * Functional tests.
 *
 * Add different text formats via the admin interface and create some nodes
 * with or without the no non-breaking space filter.
 *
 * @group no_nbsp
 */
class FunctionalTest extends NoNbspWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['no_nbsp'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['name' => 'page', 'type' => 'page']);
    $this->user = $this->drupalCreateUser([
      'administer filters',
      'bypass node access',
      'administer content types',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests the format administration functionality.
   */
  public function testFormatAdmin() {
    // Check format add page.
    $this->drupalGet('admin/config/content/formats');
    $this->clickLink('Add text format');
    $this->assertText(t('No Non-breaking Space Filter'), 'Title text is shown.');
    $this->assertText(t('Delete all non-breaking space HTML entities.'), 'Description text is shown.');

    // Add new format.
    $format_id = 'no_nbsp_format';
    $name = 'No nbsp filter format';
    $edit = [
      'format' => $format_id,
      'name' => $name,
      'roles[anonymous]' => 1,
      'roles[authenticated]' => 1,
      'filters[filter_no_nbsp][status]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Text the filters tips.
    $this->drupalGet('filter/tips');
    $this->assertText(t('All non-breaking space HTML entities are replaced by blank space characters.'));
    $this->assertText(t('Multiple contiguous space characters are replaced by a single blank space character.'));

    // Show submitted format edit page.
    $this->drupalGet('admin/config/content/formats/manage/' . $format_id);

    $input = $this->xpath('//input[@id="edit-filters-filter-no-nbsp-status"]');
    $this->assertEqual($input[0]->attributes()->checked, 'checked');

    // Test the format object.
    filter_formats_reset();
    $formats = filter_formats();
    $this->assertIdentical($formats[$format_id]->get('name'), $name);

    // Check format overview page.
    $this->drupalGet('admin/config/content/formats');
    $this->assertText($name);

    // Generate a page without the enabled text filter.
    $node = $this->createFormatAndNode('l&nbsp;&nbsp;&nbsp;o&nbsp;&nbsp;&nbsp;l', 0);
    $this->assertRaw('l&nbsp;&nbsp;&nbsp;o&nbsp;&nbsp;&nbsp;l');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // no_nbsp_format exists at this time.
    $this->assertText(t('All non-breaking space HTML entities are replaced by blank space characters.'));
    $this->assertNoText(t('Multiple contiguous space characters are replaced by a single blank space character.'));

    // Generate a page with the enabled text filter.
    $node = $this->createFormatAndNode('l&nbsp;&nbsp;&nbsp;o&nbsp;&nbsp;&nbsp;l', 1);
    $this->assertRaw('l o l');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertText(t('All non-breaking space HTML entities are replaced by blank space characters.'));
    $this->assertNoText(t('Multiple contiguous space characters are replaced by a single blank space character.'));
  }

}
