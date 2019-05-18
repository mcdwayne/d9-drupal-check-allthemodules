<?php

namespace Drupal\Tests\bulkentity\Functional;

use Drupal\bulkentity\EntityLoader;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Class BatchEntityLoaderTest
 *
 * @group bulkentity
 */
class EntityLoaderTest extends BrowserTestBase {

  protected static $modules = ['node', 'contentserialize'];

  /**
   * An array of node IDs of test articles.
   *
   * @var int[]
   */
  protected $ids;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);

    // Create a node that should never be returned.
    Node::create([
      'type' => 'page',
      'title' => 'Page 1',
    ])->save();

    for ($i = 1; $i <= 6; $i++) {
      $values = [
        'title' => "Article $i",
        'type' => 'article',
      ];

      $node = Node::create($values);
      $node->save();
      $this->ids[] = $node->id();
    }
  }

  /**
   * Test that the correct entities are loaded by ID.
   */
  public function testLoadByIds() {
    $loader = new EntityLoader(\Drupal::entityTypeManager());
    $i = 1;
    foreach ($loader->byIds(2, $this->ids, 'node') as $nid => $node) {
      $this->assertEquals("Article $i", $node->label());
      $i++;
    };
  }

  /**
   * Test that the correct entities are loaded by query.
   */
  public function testByQuery() {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->sort('title');
    $loader = new EntityLoader(\Drupal::entityTypeManager());
    $i = 1;
    foreach ($loader->byQuery(2, $query) as $node) {
      $this->assertEquals("Article $i", $node->label());
      $i++;
    }
  }

  /**
   * Test that the correct entities are loaded by entity type and bundle.
   */
  public function testByEntityType() {
    $loader = new EntityLoader(\Drupal::entityTypeManager());
    $i = 1;
    foreach ($loader->byEntityType(2, 'node', ['article']) as $node) {
      $this->assertEquals("Article $i", $node->label());
      $i++;
    }
  }

}
