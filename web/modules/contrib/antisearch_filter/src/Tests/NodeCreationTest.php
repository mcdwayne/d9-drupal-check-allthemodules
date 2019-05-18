<?php

namespace Drupal\antisearch_filter\Tests;

/**
 * Create nodes.
 *
 * @group antisearch_filter
 */
class NodeCreationTest extends AntisearchWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['antisearch_filter'];

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
      'edit any page content',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Create a new text format and a new node.
   *
   * @param string $text
   *   Body text of the node.
   * @param array $settings
   *   An array with antisearch filter settings.
   */
  protected function createTextFormatAndNode($text, array $settings = []) {
    $defaults = [
      'antisearch_filter_email' => FALSE,
      'antisearch_filter_strike' => FALSE,
      'antisearch_filter_bracket' => FALSE,
      'antisearch_filter_show_title' => FALSE,
    ];
    $settings += $defaults;
    $format = $this->createTextFormatWeb(strtolower($this->randomMachineName()), $settings);
    filter_formats_reset();
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $text;
    $edit['body[0][format]'] = $format->get('name');
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet('node/' . $node->id());
    return $node;
  }

  /**
   * Check the filter markup using regular expressions.
   */
  protected function assertFilterHtml() {
    $this->assertTrue(preg_match('#<span class="antisearch-filter".*>l<i>.</i>o<i>.</i>l<i>.</i></span>#',
                      $this->getRawContent()));
  }

  /**
   * Create nodes with different filter settings.
   */
  public function testDifferentSettings() {
    $this->createTextFormatWeb('anti_one');
    $this->createTextFormatWeb('anti_two');

    // // Nothing enabled.
    $node = $this->createTextFormatAndNode('<s>lol</s>');
    $node = $this->assertRaw('<s>lol</s>');

    // Check if CSS is loaded.
    $this->assertRaw('antisearch_filter/antisearch_filter.css');

    // Strike (s).
    $node = $this->createTextFormatAndNode(
      '<s>lol</s>',
      ['antisearch_filter_strike' => TRUE]
    );
    $this->assertFilterHTML();

    // Regular expression is non greedy.
    $node = $this->createTextFormatAndNode(
      '<s>lol</s> normal text <s>lol</s>',
      ['antisearch_filter_strike' => TRUE]
    );
    $this->assertRaw('normal text');

    // Strike.
    $node = $this->createTextFormatAndNode(
      '<strike>lol</strike>',
      ['antisearch_filter_strike' => TRUE]
    );
    $this->assertFilterHTML();

    // Bracket.
    $node = $this->createTextFormatAndNode(
      '[lol]',
      ['antisearch_filter_bracket' => TRUE]
    );
    $this->assertFilterHTML();

    // Email.
    $node = $this->createTextFormatAndNode(
      'josef@friedrich.rocks',
      ['antisearch_filter_email' => TRUE]
    );
    $this->assertRaw('<span class="antisearch-filter"');

    // Description.
    $node = $this->createTextFormatAndNode(
      'josef@friedrich.rocks',
      ['antisearch_filter_email' => TRUE, 'antisearch_filter_show_title' => TRUE]
    );
    $this->assertRaw('The text is hidden from search engines.');
  }

}
