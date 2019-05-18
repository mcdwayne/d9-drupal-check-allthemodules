<?php

namespace Drupal\Tests\entity_usage\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;

/**
 * Basic functional tests for the usage tracking plugins.
 *
 * @package Drupal\Tests\entity_usage\FunctionalJavascript
 *
 * @group entity_usage
 */
class IntegrationTest extends EntityUsageJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
  ];

  /**
   * Tests the tracking of nodes in some simple CRUD operations.
   */
  public function testCrudTracking() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    /** @var \Drupal\entity_usage\EntityUsage $usage_service */
    $usage_service = \Drupal::service('entity_usage.usage');

    // Create node 1.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 1');
    $page->pressButton('Save');
    $assert_session->pageTextContains('eu_test_ct Node 1 has been created.');
    $this->saveHtmlOutput();
    $node1 = Node::load(1);

    // Create node 2 referencing node 1 using reference field.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 2');
    $page->fillField('field_eu_test_related_nodes[0][target_id]', 'Node 1 (1)');
    $page->pressButton('Save');
    $assert_session->pageTextContains('eu_test_ct Node 2 has been created.');
    $this->saveHtmlOutput();
    $node2 = Node::load(2);
    // Check that we registered correctly the relation between N2 and N1.
    $usage = $usage_service->listUsage($node1);
    $this->assertEquals($usage['node'], ['2' => '1'], 'Correct usage found.');
    // Check that the method stored for the tracking is "entity_reference".
    $usage = $usage_service->listUsage($node1, TRUE);
    $this->assertEquals($usage['entity_reference']['node'], ['2' => '1'], 'Correct usage found.');

    // Create node 3 referencing node 2 using embedded text.
    // $this->drupalGet('/node/add/eu_test_ct'); .
    // $page->fillField('title[0][value]', 'Node 3'); .
    // @TODO ^ The Ckeditor is creating some trouble to do this in a simple way.
    // For now let's just avoid all this ckeditor interaction (which is not what
    // we are really testing) and create a node programatically, which triggers
    // the tracking as well.
    $uuid_node2 = $node2->uuid();
    $embedded_text = '<drupal-entity data-embed-button="node" data-entity-embed-display="entity_reference:entity_reference_label" data-entity-embed-display-settings="{&quot;link&quot;:1}" data-entity-type="node" data-entity-uuid="' . $uuid_node2 . '"></drupal-entity>';
    $node3 = Node::create([
      'type' => 'eu_test_ct',
      'title' => 'Node 3',
      'field_eu_test_rich_text' => [
        'value' => $embedded_text,
        'format' => 'eu_test_text_format',
      ],
    ]);
    $node3->save();
    // Check that we registered correctly the relation between N3 and N2.
    $usage = $usage_service->listUsage($node2);
    $this->assertEquals($usage['node'], ['3' => '1'], 'Correct usage found.');
    // Check that the method stored for the tracking is "entity_embed".
    $usage = $usage_service->listUsage($node2, TRUE);
    $this->assertEquals($usage['entity_embed']['node'], ['3' => '1'], 'Correct usage found.');

    // Create node 4 referencing node 2 using both methods.
    $node4 = Node::create([
      'type' => 'eu_test_ct',
      'title' => 'Node 4',
      'field_eu_test_related_nodes' => [
        'target_id' => '2',
      ],
      'field_eu_test_rich_text' => [
        'value' => $embedded_text,
        'format' => 'eu_test_text_format',
      ],
    ]);
    $node4->save();
    // Check that we registered correctly the relation between N4 and N2.
    $usage = $usage_service->listUsage($node2);
    $expected_count = [
      'node' => [
        '3' => '1',
        '4' => '2',
      ],
    ];
    $this->assertEquals($usage['node'], $expected_count['node'], 'Correct usage found.');

    // Delete node 2 and verify that we clean up usages.
    $node2->delete();
    $usage = $usage_service->listUsage($node1);
    $this->assertEquals($usage, [], 'Usage for node1 correctly cleaned up.');
    $database = \Drupal::database();
    $count = $database->select('entity_usage', 'e')
      ->fields('e', ['count'])
      ->condition('e.t_type', 'node')
      ->condition('e.t_id', '2')
      ->execute()
      ->fetchField();
    $this->assertSame(FALSE, $count, 'Usage for node2 correctly cleaned up.');

    // Create node 5 referencing node 4 using a linkit markup.
    $embedded_text = '<p>foo <a data-entity-substitution="canonical" data-entity-type="node" data-entity-uuid="' . $node4->uuid() . '">linked text</a> bar</p>';
    $node5 = Node::create([
      'type' => 'eu_test_ct',
      'title' => 'Node 5',
      'field_eu_test_rich_text' => [
        'value' => $embedded_text,
        'format' => 'eu_test_text_format',
      ],
    ]);
    $node5->save();
    // Check that we registered correctly the relation between N5 and N2.
    $usage = $usage_service->listUsage($node4);
    $this->assertEquals($usage['node'], ['5' => '1'], 'Correct usage found.');
    // Check that the method stored for the tracking is "linkit".
    $usage = $usage_service->listUsage($node4, TRUE);
    $this->assertEquals($usage['linkit']['node'], ['5' => '1'], 'Correct usage found.');
  }

  /**
   * Tests the tracking of nodes in link fields.
   */
  public function testLinkTracking() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    /** @var \Drupal\entity_usage\EntityUsage $usage_service */
    $usage_service = \Drupal::service('entity_usage.usage');

    // Add a link field to our test content type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_link1',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'eu_test_ct',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ]);
    $field->save();

    entity_get_form_display('node', 'eu_test_ct', 'default')
      ->setComponent('field_link1', ['type' => 'link_default'])
      ->save();

    entity_get_display('node', 'eu_test_ct', 'default')
      ->setComponent('field_link1', ['type' => 'link'])
      ->save();

    // Create Node 1.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 1');
    $page->pressButton('Save');
    $assert_session->pageTextContains('eu_test_ct Node 1 has been created.');
    $this->saveHtmlOutput();
    $node1_id = \Drupal::entityQuery('node')
      ->sort('changed', 'DESC')
      ->execute();
    $node1_id = reset($node1_id);
    $this->assertNotNull($node1_id);
    $node1 = \Drupal::entityTypeManager()->getStorage('node')
      ->loadUnchanged($node1_id);

    // Create Node 2, referencing Node 1.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 2');
    $page->fillField('field_link1[0][uri]', "Node 1 ($node1_id)");
    $page->fillField('field_link1[0][title]', "Linked text");
    $page->pressButton('Save');
    $assert_session->pageTextContains('eu_test_ct Node 2 has been created.');
    $this->saveHtmlOutput();
    $node2_id = \Drupal::entityQuery('node')
      ->sort('changed', 'DESC')
      ->execute();
    $node2_id = reset($node2_id);
    $this->assertNotNull($node2_id);
    $node2 = \Drupal::entityTypeManager()->getStorage('node')
      ->loadUnchanged($node2_id);
    // Check that the usage of Node 1 points to Node 2.
    $usage = $usage_service->listUsage($node1, TRUE);
    $this->assertEquals([$node2_id => '1'], $usage['link']['node']);

    // Edit Node 2, remove reference.
    $this->drupalGet("/node/{$node2_id}/edit");
    $page->fillField('field_link1[0][uri]', '');
    $page->fillField('field_link1[0][title]', '');
    $page->pressButton('Save');
    $assert_session->pageTextContains('eu_test_ct Node 2 has been updated.');
    $this->saveHtmlOutput();
    // Verify the usage was released.
    $usage = $usage_service->listUsage($node1);
    $this->assertEquals([], $usage);

    // Reference Node 1 again, and then delete the host node.
    $this->drupalGet("/node/{$node2_id}/edit");
    $page->fillField('field_link1[0][uri]', "Node 1 ($node1_id)");
    $page->fillField('field_link1[0][title]', "Linked text");
    $page->pressButton('Save');
    $assert_session->pageTextContains('eu_test_ct Node 2 has been updated.');
    $this->saveHtmlOutput();
    // Usage now should be there.
    $usage = $usage_service->listUsage($node1, TRUE);
    $this->assertEquals([$node2_id => '1'], $usage['link']['node']);
    // Delete the host and usage should be released.
    $node2->delete();
    $usage = $usage_service->listUsage($node1);
    $this->assertEquals([], $usage);
  }

}
