<?php

namespace Drupal\footnotes\Tests;

// Use of base class for the tests.
use Drupal\simpletest\WebTestBase;

// Necessary for constants.
use Drupal\Core\Session\AccountInterface;

/**
 * Tests for Footnotes in node content.
 *
 * Those tests are for the content of the node, to make sure they are
 * processed by footnotes.
 *
 * @group footnotes
 */
class FootnoteTest extends WebTestBase {

  /**
   * A global filter administrator.
   *
   * @var object
   */
  protected $filterAdminUser;

  /**
   * List of modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'footnotes', 'filter'];

  /**
   * The number of current node.
   *
   * @var int
   */
  protected $node;

  /**
   * Code run before each and every test method.
   */
  public function setUp() {
    parent::setUp();

    // Restore node to default value.
    $this->node = 1;

    // Create a content type, as we will create nodes on test.
    $settings = [
      // Override default type (a random name).
      'type' => 'footnotes_content_type',
      'name' => 'Footnotes Content',
    ];
    $this->drupalCreateContentType($settings);

    // Create a filter admin user.
    $permissions = [
      'administer filters',
      'administer nodes',
      'access administration pages',
      'create footnotes_content_type content',
      'edit any footnotes_content_type content',
      'administer site configuration',
    ];
    $this->filterAdminUser = $this->drupalCreateUser($permissions);

    // Log in with filter admin user.
    $this->drupalLogin($this->filterAdminUser);

    // Add an text format with only geshi filter.
    $this->createTextFormat('footnotes_text_format', ['filter_footnotes']);
  }

  /**
   * Create a new text format.
   *
   * @param string $format_name
   *   The name of new text format.
   * @param array $filters
   *   Array with the machine names of filters to enable.
   */
  protected function createTextFormat($format_name, array $filters) {
    $edit = [];
    $edit['format'] = $format_name;
    $edit['name'] = $this->randomMachineName();
    $edit['roles[' . AccountInterface::AUTHENTICATED_ROLE . ']'] = 1;
    foreach ($filters as $filter) {
      $edit['filters[' . $filter . '][status]'] = TRUE;
    }
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    $this->assertRaw(t('Added text format %format.', ['%format' => $edit['name']]), 'New filter created.');
    $this->drupalGet('admin/config/content/formats');
  }

  /**
   * Test the node content.
   *
   * Maybe split this function in smaller tests latter, but it takes lass time
   * to run this way.
   * There we test if the footnotes are converted using [fn] and <fn>, and test
   * if the css file is included.
   */
  protected function testNodeContent() {
    $text1 = 'This is the note one.';
    $note1 = '[fn]' . $text1 . '[/fn]';
    $text2 = 'And this is the note two.';
    $note2 = "<fn>$text2</fn>";
    $body = '<p>' . $this->randomMachineName(100) . $note1 . '</p><p>' .
      $this->randomMachineName(100) . $note2 . '</p>';
    // Create a node.
    $node = [
      'title' => 'Test for Footnotes',
      'body' => [
        [
          'value' => $body,
          'format' => 'footnotes_text_format',
        ],
      ],
      'type' => 'footnotes_content_type',
    ];
    $this->drupalCreateNode($node);

    $this->drupalGet('node/' . $this->node);
    $this->node++;
    // Footnote with [fn].
    $this->assertNoRaw($note1, 'Footnote: ' . htmlentities($note1) . " do not show as raw.");
    $this->assertText($text1, 'Footnote: ' . $text1 . ' show as text.');
    // Footnote with <fn>.
    $this->assertNoRaw($note2, 'Footnote: ' . htmlentities($note2) . " do not show as raw.");
    $this->assertText($text2, 'Footnote: ' . $text2 . ' show as text.');

    // Css file:
    $this->assertRaw('/assets/css/footnotes.css', 'The css file /assets/css/footnotes is present.');
  }

}
