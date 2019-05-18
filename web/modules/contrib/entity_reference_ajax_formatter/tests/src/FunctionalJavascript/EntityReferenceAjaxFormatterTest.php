<?php

namespace Drupal\Tests\entity_reference_ajax_formatter\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;

/**
 * Test the Entity Reference Ajax Formatter.
 *
 * @group entity_reference_ajax_formatter
 */
class EntityReferenceAjaxFormatterTest extends WebDriverTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'entity_reference_ajax_formatter',
  ];

  /**
   * The Entity View Display for the article node type.
   *
   * @var \Drupal\Core\Entity\Entity\EntityViewDisplay
   */
  protected $display;

  /**
   * The primary node to be testing.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $user = $this->drupalCreateUser(['create article content', 'edit own article content']);
    $this->drupalLogin($user);
    $entityTypeManager = $this->container->get('entity_type.manager');
    FieldStorageConfig::create([
      'field_name' => 'field_ref',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_ref',
      'label' => 'Node references',
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => [
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => [
            'article' => 'article',
          ],
          'sort' => [
            'field' => '_none',
          ],
        ],
      ],
    ])->save();
    $this->display = $entityTypeManager->getStorage('entity_view_display')
      ->load('node.article.default');
    $this->display->setComponent('field_ref', [
      'type' => 'entity_reference_ajax_entity_view',
      'settings' => [
        'number' => 2,
      ],
    ])->save();
    $nodes = [];
    $i = 0;
    while ($i <= 9) {
      $node = Node::create([
        'title' => "Node #{$i}",
        'type' => 'article',
      ]);
      $node->save();
      $nodes[] = ['target_id' => $node->id()];
      $i++;
    }
    $this->node = Node::create([
      'title' => 'Primary Node',
      'type' => 'article',
      'field_ref' => $nodes,
    ]);
    $this->node->save();
  }

  /**
   * Tests the behavior of the 'entity_reference_ajax_entity_view' formatter.
   */
  public function testFormatter() {
    $this->drupalGet("node/{$this->node->id()}");
    $session = $this->assertSession();
    $session->pageTextContains('Node #1');
    $session->pageTextNotContains('Node #2');
    $session->pageTextNotContains('Load More');

    // Test random sort.
    $this->display->setComponent('field_ref', [
      'type' => 'entity_reference_ajax_entity_view',
      'settings' => [
        'number' => 3,
        'sort' => 1,
        'load_more' => TRUE,
        'max' => 8,
      ],
    ])->save();

    $this->drupalGet("node/{$this->node->id()}");
    $page = $this->getSession()->getPage();

    $this->assertSame(count($page->findAll('css', 'article.node')), 4);
    $session->pageTextMatches('/Node #(\d\d|[^123])/');
    $session->pageTextContains('Load More');
    $page->clickLink('Load More');
    $session->assertWaitOnAjaxRequest();
    $this->assertSame(count($page->findAll('css', 'article.node')), 7);
    $session->pageTextContains('Load More');

    $page->clickLink('Load More');
    $session->assertWaitOnAjaxRequest();
    $text = $page->getText();
    $this->assertSame(count($page->findAll('css', 'article.node')), 9);
    $session->pageTextNotContains('Load More');

  }

}
