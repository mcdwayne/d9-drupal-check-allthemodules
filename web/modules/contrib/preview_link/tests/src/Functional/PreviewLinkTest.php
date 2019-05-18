<?php

namespace Drupal\Tests\preview_link\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Integration test for the preview link.
 *
 * @group preview_link
 */
class PreviewLinkTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['preview_link', 'node', 'filter'];

  /**
   * Test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * The test node.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->createUser(['generate preview links']);
    $this->createContentType(['type' => 'page']);
    $this->node = $this->createNode(['status' => NODE_NOT_PUBLISHED]);
  }

  /**
   * Test the preview link page.
   */
  public function testPreviewLinkPage() {
    $assert = $this->assertSession();
    // Can only be visited by users with correct permission.
    $url = Url::fromRoute('entity.node.generate_preview_link', [
      'node' => $this->node->id(),
    ]);
    $this->drupalGet($url);
    $assert->statusCodeEquals(403);

    $this->drupalLogin($this->admin);
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Grab the link from the page and ensure it works.
    $link = $this->cssSelect('.preview-link__link')[0]->getText();
    $this->drupalGet($link);
    $assert->statusCodeEquals(200);
    $assert->responseContains($this->node->getTitle());

    // Submitting form re-generates the link.
    $this->drupalPostForm($url, [], 'Re-generate preview link');
    $new_link = $this->cssSelect('.preview-link__link')[0]->getText();
    $this->assertNotEquals($link, $new_link);

    // Old link doesn't work.
    $this->drupalGet($link);
    $assert->statusCodeEquals(403);
    $assert->responseNotContains($this->node->getTitle());

    // New link does work.
    $this->drupalGet($new_link);
    $assert->statusCodeEquals(200);
    $assert->responseContains($this->node->getTitle());

    // Logout, new link works for anonymous user.
    $this->drupalLogout();
    $this->drupalGet($new_link);
    $assert->statusCodeEquals(200);
    $assert->responseContains($this->node->getTitle());
  }

}
