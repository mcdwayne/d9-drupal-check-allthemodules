<?php

namespace Drupal\no_nbsp\Tests;

/**
 * The no non-breaking space filter acts as a field formatter.
 *
 * @group no_nbsp
 */
class FieldFormatterTest extends NoNbspWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'no_nbsp'];

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
    $this->createTextFormatWeb('with_no_nbsp', TRUE);
    $this->createTextFormatWeb('without_no_nbsp', FALSE);

    // Check admin interface.
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertText(t('No Non-breaking Space Filter'));

    // Create node with full html support.
    $edit = [];
    $title = $this->randomMachineName();
    $edit['title[0][value]'] = $title;
    $edit['body[0][value]'] = 'l&nbsp;&nbsp;&nbsp;o&nbsp;&nbsp;&nbsp;l';
    $edit['body[0][format]'] = 'without_no_nbsp';
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($title);
    // The field formatter is not set.
    $this->assertRaw('l&nbsp;&nbsp;&nbsp;o&nbsp;&nbsp;&nbsp;l');

    // Change display.
    $edit = [
      'fields[body][type]' => 'no_nbsp',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/display', $edit, t('Save'));

    // Check if the field formatter works.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('l o l');
  }

}
