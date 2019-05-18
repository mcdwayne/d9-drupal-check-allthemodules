<?php

namespace Drupal\Tests\sel\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that links in formatted long text fields are well-handled.
 *
 * @group filter
 */
class SelLinkFilterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'node',
    'filter',
    'sel',
  ];

  /**
   * An user with permissions to create pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'test_page',
      'name' => 'Test page',
    ]);

    // Set up the filter formats used by this test.
    $basic_html_format = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a href target rel> <em>',
          ],
          'weight' => 0,
        ],
        'filter_url' => [
          'status' => TRUE,
          'weight' => 1,
        ],
        'filter_sel' => [
          'status' => TRUE,
          'weight' => 2,
        ],
      ],
    ]);
    $basic_html_format->save();

    $this->webUser = $this->drupalCreateUser([
      'create test_page content',
      'edit own test_page content',
    ]);

    $this->drupalLogin($this->webUser);
  }

  /**
   * Tests link filter.
   */
  public function testLinkFilter() {
    $absoluteHost = \Drupal::request()->getSchemeAndHttpHost();
    // Create a new node of the new node type.
    $node = Node::create([
      'type' => 'test_page',
      'title' => $this->randomString(),
    ]);
    $node->body->value = <<<EOF
<p>{$absoluteHost}/user</p>
<p>https://example.com</p>
<p>{$absoluteHost}er</p>
<p><a href="/user">User page link</a></p>
<p><a href='{$absoluteHost}/user'>Absolute user page link</a></p>
<p><a href="https://example.com">External link</a></p>
<p><a href='{$absoluteHost}er'>External alt link</a></p>
<p><a href="/user" target="_blank" rel="nofollow">Internal link with attributes</a></p>
<p><a href="https://example.com" target="_self" rel="nofollow">External link with attributes</a></p>
<p><a href="https://example.com" target="_blank" rel="noreferrer">External link with noreferrer</a></p>
EOF;
    $node->body->format = 'basic_html';
    $node->save();

    $this->drupalGet($node->urlInfo());

    //
    // Check links are processed properly.
    //
    $links = $this->xpath("//div[contains(@class, :class)]//a", [
      ':class' => 'field--name-body',
    ]);

    // Verify 'https://local.test/user' link.
    $this->assertEquals(NULL, $links[0]->getAttribute('target'));
    $this->assertEquals(NULL, $links[0]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . '/user', $links[0]->getAttribute('href'));
    $this->assertEquals($absoluteHost . '/user', $links[0]->getText());

    // Verify 'https://example.com' link.
    $this->assertEquals('_blank', $links[1]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[1]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[1]->getAttribute('href'));
    $this->assertEquals('https://example.com', $links[1]->getText());

    // Verify 'https://local.tester' link.
    $this->assertEquals('_blank', $links[2]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[2]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . 'er', $links[2]->getAttribute('href'));
    $this->assertEquals($absoluteHost . 'er', $links[2]->getText());

    // Verify '/user' link with label 'User page link'.
    $this->assertEquals(NULL, $links[3]->getAttribute('target'));
    $this->assertEquals(NULL, $links[3]->getAttribute('rel'));
    $this->assertEquals('/user', $links[3]->getAttribute('href'));
    $this->assertEquals('User page link', $links[3]->getText());

    // Verify 'https://local.test/user' link with label
    // 'Absolute user page link'.
    $this->assertEquals(NULL, $links[4]->getAttribute('target'));
    $this->assertEquals(NULL, $links[4]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . '/user', $links[4]->getAttribute('href'));
    $this->assertEquals('Absolute user page link', $links[4]->getText());

    // Verify 'https://example.com' link with label 'External link'.
    $this->assertEquals('_blank', $links[5]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[5]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[5]->getAttribute('href'));
    $this->assertEquals('External link', $links[5]->getText());

    // Verify 'https://local.tester' link with label 'External alt link'.
    $this->assertEquals('_blank', $links[6]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[6]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . 'er', $links[6]->getAttribute('href'));
    $this->assertEquals('External alt link', $links[6]->getText());

    // Verify '/user' link with attributes and with label
    // 'Internal link with attributes'.
    $this->assertEquals('_blank', $links[7]->getAttribute('target'));
    $this->assertEquals('nofollow', $links[7]->getAttribute('rel'));
    $this->assertEquals('/user', $links[7]->getAttribute('href'));
    $this->assertEquals('Internal link with attributes', $links[7]->getText());

    // Verify 'https://example.com' link with attributes and label
    // 'External link with attributes'.
    $this->assertEquals('_blank', $links[8]->getAttribute('target'));
    $this->assertEquals(TRUE, strpos($links[8]->getAttribute('rel'), 'noreferrer') !== FALSE);
    $this->assertEquals(TRUE, strpos($links[8]->getAttribute('rel'), 'nofollow') !== FALSE);
    $this->assertEquals('https://example.com', $links[8]->getAttribute('href'));
    $this->assertEquals('External link with attributes', $links[8]->getText());

    // Verify 'https://example.com' link with attributes and label
    // 'External link with noreferrer'.
    $this->assertEquals('_blank', $links[9]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[9]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[9]->getAttribute('href'));
    $this->assertEquals('External link with noreferrer', $links[9]->getText());

    $this->drupalLogout();
    $this->drupalGet($node->urlInfo());

    //
    // Re-check links as Anonymous.
    //
    $links = $this->xpath("//div[contains(@class, :class)]//a", [
      ':class' => 'field--name-body',
    ]);

    // Verify 'https://local.test/user' link.
    $this->assertEquals(NULL, $links[0]->getAttribute('target'));
    $this->assertEquals(NULL, $links[0]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . '/user', $links[0]->getAttribute('href'));
    $this->assertEquals($absoluteHost . '/user', $links[0]->getText());

    // Verify 'https://example.com' link.
    $this->assertEquals('_blank', $links[1]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[1]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[1]->getAttribute('href'));
    $this->assertEquals('https://example.com', $links[1]->getText());

    // Verify 'https://local.tester' link.
    $this->assertEquals('_blank', $links[2]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[2]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . 'er', $links[2]->getAttribute('href'));
    $this->assertEquals($absoluteHost . 'er', $links[2]->getText());

    // Verify '/user' link with label 'User page link'.
    $this->assertEquals(NULL, $links[3]->getAttribute('target'));
    $this->assertEquals(NULL, $links[3]->getAttribute('rel'));
    $this->assertEquals('/user', $links[3]->getAttribute('href'));
    $this->assertEquals('User page link', $links[3]->getText());

    // Verify 'https://local.test/user' link with label
    // 'Absolute user page link'.
    $this->assertEquals(NULL, $links[4]->getAttribute('target'));
    $this->assertEquals(NULL, $links[4]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . '/user', $links[4]->getAttribute('href'));
    $this->assertEquals('Absolute user page link', $links[4]->getText());

    // Verify 'https://example.com' link with label 'External link'.
    $this->assertEquals('_blank', $links[5]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[5]->getAttribute('rel'));
    $this->assertEquals('https://example.com', $links[5]->getAttribute('href'));
    $this->assertEquals('External link', $links[5]->getText());

    // Verify 'https://local.tester' link with label 'External alt link'.
    $this->assertEquals('_blank', $links[6]->getAttribute('target'));
    $this->assertEquals('noreferrer', $links[6]->getAttribute('rel'));
    $this->assertEquals($absoluteHost . 'er', $links[6]->getAttribute('href'));
    $this->assertEquals('External alt link', $links[6]->getText());
  }

}
