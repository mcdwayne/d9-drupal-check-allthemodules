<?php

namespace Drupal\nodeorder\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Test CRUD operations that Nodeorder relies on.
 *
 * @group nodeorder
 */
class NodeorderCrudTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'node', 'nodeorder'];

  /**
   * The node order manager.
   *
   * @var \Drupal\nodeorder\NodeOrderManagerInterface
   */
  protected $nodeOrderManager;

  /**
   * Taxonomy term reference field for testing.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $field;

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\taxonomy\Tests\TermTest::setUp()
   */
  public function setUp() {
    parent::setUp();

    $this->nodeOrderManager = $this->container->get('nodeorder.manager');

    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy', 'bypass node access', 'order nodes within categories'
    ]));
    $this->vocabulary = $this->createVocabulary();

    $field_name = 'taxonomy_' . $this->vocabulary->id();
    entity_create('field_storage_config', [
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'module' => 'taxonomy',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ])->save();

    $this->field = entity_create('field_config', [
      'field_name' => $field_name,
      'bundle' => 'article',
      'entity_type' => 'node',
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $this->vocabulary->id() => $this->vocabulary->id(),
          ],
          'sort' => [
            'field' => '_none',
          ],
          'auto_create' => FALSE,
        ],
      ],
    ]);
    $this->field->save();
    entity_get_form_display('node', 'article', 'default')
      ->setComponent($field_name, [
        'type' => 'options_select',
      ])
      ->save();
    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
      ])
      ->save();
  }

  /**
   * Test orderable vocabularies.
   */
  public function testOrderableVocabulary() {
    // Vocabulary should default to not being orderable.
    $this->assertFalse($this->nodeOrderManager->vocabularyIsOrderable($this->vocabulary->id()), 'The test vocabulary is not orderable by default.');

    // Enable 'orderable' on this vocabulary.
    \Drupal::configFactory()->getEditable('nodeorder.settings')
      ->set('vocabularies', [$this->vocabulary->id() => TRUE])
      ->save();

    // Ensure the vocabulary is sortable.
    $this->assertTrue($this->nodeOrderManager->vocabularyIsOrderable($this->vocabulary->id()), 'The test vocabulary is orderable.');
  }

  /**
   * Test node CRUD operations that nodeorder depends on.
   */
  public function testNodeCrudOperations() {
    // Enable 'orderable' on this vocabulary.
    \Drupal::configFactory()->getEditable('nodeorder.settings')
      ->set('vocabularies', [$this->vocabulary->id() => TRUE])
      ->save();

    // Create two taxonomy terms.
    $term1 = $this->createTerm($this->vocabulary);
    $term2 = $this->createTerm($this->vocabulary);

    // Create several nodes in both terms.
    $node1 = [
      'type' => 'article',
      'title' => 'aaaa',
      $this->field->getName() => [
        ['target_id' => $term1->id()],
        ['target_id' => $term2->id()],
      ],
    ];
    $node1 = $this->drupalCreateNode($node1);
    $node2 = [
      'type' => 'article',
      'title' => 'bbbb',
      $this->field->getName() => [
        ['target_id' => $term1->id()],
        ['target_id' => $term2->id()],
      ],
    ];
    $node2 = $this->drupalCreateNode($node2);

    // Initial order should be identical in each term.
    $expected = [
      $node1->id() => 0,
      $node2->id() => -1,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Re-order nodes in term 1.
    $edit = [
      'entities[' . $node1->id() . '][weight]' => -1,
      'entities[' . $node2->id() . '][weight]' => 1,
    ];
    $this->drupalPostForm('taxonomy/term/' . $term1->id() . '/order', $edit, t('Save'));

    // Order in term 2 should remain unchanged.
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Verify order in term 1.
    $expected = [
      $node1->id() => -1,
      $node2->id() => 1,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);

    // Add a third node.
    $node3 = [
      'type' => 'article',
      'title' => 'cccc',
      $this->field->getName() => [
        ['target_id' => $term1->id()],
        ['target_id' => $term2->id()],
      ],
    ];
    $node3 = $this->drupalCreateNode($node3);

    // New node should be at the very top in both terms.
    $expected[$node3->id()] = -2;
    $this->assertNodeorderByTid($term1->id(), $expected);
    $expected = [
      $node1->id() => 0,
      $node2->id() => -1,
      $node3->id() => -2,
    ];
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Move node 1 back to the top in term 1.
    $edit = [
      'entities[' . $node1->id() . '][weight]' => -2,
      'entities[' . $node2->id() . '][weight]' => 1,
      'entities[' . $node3->id() . '][weight]' => 2,
    ];
    $this->drupalPostForm('taxonomy/term/' . $term1->id() . '/order', $edit, t('Save'));
    // Order in term 2 should remain the same.
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Verify order in term 1.
    $expected = [
      $node1->id() => -2,
      $node2->id() => 1,
      $node3->id() => 2,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);

    // Delete a node and verify orderings.
    $node2->delete();
    $expected = [
      $node1->id() => -1,
      $node3->id() => 0,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);
    $expected = [
      $node1->id() => 0,
      $node3->id() => -1,
    ];
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Add a new node.
    $node4 = [
      'type' => 'article',
      'title' => 'dddd',
      $this->field->getName() => [
        ['target_id' => $term1->id()],
        ['target_id' => $term2->id()],
      ],
    ];
    $node4 = $this->drupalCreateNode($node4);

    // New node should be at the top of each term.
    $expected = [
      $node1->id() => -1,
      $node3->id() => 0,
      $node4->id() => -2,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);

    $expected = [
      $node1->id() => 0,
      $node3->id() => -1,
      $node4->id() => -2,
    ];
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Move node 1 back to the top in term 1.
    $edit = [
      'entities[' . $node1->id() . '][weight]' => -2,
      'entities[' . $node3->id() . '][weight]' => 1,
      'entities[' . $node4->id() . '][weight]' => 2,
    ];
    $this->drupalPostForm('taxonomy/term/' . $term1->id() . '/order', $edit, t('Save'));
    // Order in term 2 should remain the same.
    $this->assertNodeorderByTid($term2->id(), $expected);

    // Verify order in term 1.
    $expected = [
      $node1->id() => -2,
      $node3->id() => 1,
      $node4->id() => 2,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);

    // Remove term 1 from node 3.
    $node3->{$this->field->getName()} = [
      ['target_id' => $term2->id()],
    ];
    $node3->save();

    // Verify order in term 1.
    $expected = [
      $node1->id() => -1,
      $node4->id() => 0,
    ];
    $this->assertNodeorderByTid($term1->id(), $expected);

    // Term 2 ordering should remain unchanged.
    $expected = [
      $node1->id() => 0,
      $node3->id() => -1,
      $node4->id() => -2,
    ];
    $this->assertNodeorderByTid($term2->id(), $expected);
  }

  /**
   * Tests actual order against an expected order.
   *
   * @param int $tid
   *   Term ID to check order against.
   * @param array $expected
   *   Expected order keyed by node ID.
   */
  protected function assertNodeorderByTid($tid, array $expected) {
    $order = db_select('taxonomy_index', 'ti')
      ->fields('ti', ['nid', 'weight'])
      ->condition('tid', $tid)
      ->execute()
      ->fetchAllAssoc('nid');

    foreach ($order as $nid => $row) {
      if (isset($expected[$nid]) && $expected[$nid] == $row->weight) {
        unset($expected[$nid]);
        unset($order[$nid]);
      }
    }

    if (!empty($expected) || !empty($order)) {
      debug($expected, 'Orderings that did not match.');
      debug($order, 'Orderings found that were not expected.');
      $this->fail('Order did not match.');
    }
    else {
      $this->pass('Order of nodes matched.');
    }
  }

}
