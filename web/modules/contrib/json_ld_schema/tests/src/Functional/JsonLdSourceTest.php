<?php

namespace Drupal\Tests\json_ld_schema\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the rendering of JSON LD scripts.
 *
 * @group json_ld_schema
 */
class JsonLdSourceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'json_ld_schema_test_sources',
    'node',
  ];

  /**
   * Test rendering of the scripts.
   */
  public function testRendering() {
    $this->drupalGet('<front>');
    $html = $this->getSession()->getPage()->getHtml();
    $this->assertContains('<script type="application/ld+json">{"@context":"http:\/\/schema.org","@type":"Thing","name":"Foo"}</script>', $html);
    $this->assertContains('<script type="application/ld+json">{"@context":"http:\/\/schema.org","@type":"Thing","name":"Bar"}</script>', $html);
    $this->assertNotContains('<script type="application/ld+json">{"@context":"http:\/\/schema.org","@type":"Thing","name":"Baz"}</script>', $html);
  }

  /**
   * Test the node source has correct render caching.
   */
  public function testNodeSourceRenderCaching() {
    NodeType::create([
      'type' => 'example',
      'label' => 'Example',
    ])->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Example A',
    ]);
    $node->save();

    // Render cache should display "5" for both hits, even after the comment
    // count changes.
    $this->setNodeCommentCount(5);
    $this->drupalGet($node->toUrl());
    $this->assertContains('"commentCount":5', $this->getSession()->getPage()->getHtml());
    $this->setNodeCommentCount(10);
    $this->drupalGet($node->toUrl());
    $this->assertContains('"commentCount":5', $this->getSession()->getPage()->getHtml());

    // A second node will display the updated comment count.
    $second_node = Node::create([
      'type' => 'example',
      'title' => 'Example B',
    ]);
    $second_node->save();
    $this->drupalGet($second_node->toUrl());
    $this->assertContains('"commentCount":10', $this->getSession()->getPage()->getHtml());
  }

  /**
   * Set the comment count that will appear on a node.
   *
   * @param int $count
   *   The comment count.
   */
  protected function setNodeCommentCount($count) {
    \Drupal::state()->set('json_ld_schema_test_sources_node_comment_count', $count);
  }

}
