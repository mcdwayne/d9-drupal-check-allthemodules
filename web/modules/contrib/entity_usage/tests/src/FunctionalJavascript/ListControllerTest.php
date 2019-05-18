<?php

namespace Drupal\Tests\entity_usage\FunctionalJavascript;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Tests the page listing the usage of a given entity.
 *
 * @package Drupal\Tests\entity_usage\FunctionalJavascript
 *
 * @group entity_usage
 */
class ListControllerTest extends EntityUsageJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Grant the logged-in user permission to see the statistics page.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['access entity usage statistics']);
  }

  /**
   * Tests the page listing the usage of entities.
   *
   * @covers \Drupal\entity_usage\Controller\ListUsageController::listUsagePage
   */
  public function testListController() {

    $page = $this->getSession()->getPage();

    // Create node 1.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 1');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('eu_test_ct Node 1 has been created.');
    $node1 = Node::load(1);
    $this->saveHtmlOutput();

    // Create node 2 referencing node 1 using reference field.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 2');
    $page->fillField('field_eu_test_related_nodes[0][target_id]', 'Node 1 (1)');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('eu_test_ct Node 2 has been created.');
    $node2 = Node::load(2);
    $this->saveHtmlOutput();

    // Create node 3 also referencing node 1 in an embed text field.
    $uuid_node1 = $node1->uuid();
    $embedded_text = '<drupal-entity data-embed-button="node" data-entity-embed-display="entity_reference:entity_reference_label" data-entity-embed-display-settings="{&quot;link&quot;:1}" data-entity-type="node" data-entity-uuid="' . $uuid_node1 . '"></drupal-entity>';
    $node3 = Node::create([
      'type' => 'eu_test_ct',
      'title' => 'Node 3',
      'field_eu_test_rich_text' => [
        'value' => $embedded_text,
        'format' => 'eu_test_text_format',
      ],
    ]);
    $node3->save();

    // Visit the page that tracks usage of node 1 and check everything is there.
    $this->drupalGet('/admin/content/entity-usage/node/1');
    $this->assertSession()->pageTextContains('Entity usage information for Node 1');

    // Check table headers are present.
    $this->assertSession()->pageTextContains('Referencing entity');
    $this->assertSession()->pageTextContains('Referencing entity type');
    $this->assertSession()->pageTextContains('Referencing method');
    $this->assertSession()->pageTextContains('Count');

    // Check both referencing nodes are linked.
    $this->assertSession()->linkExists('Node 2');
    $this->assertSession()->linkByHrefExists('/node/2');
    $this->assertSession()->linkExists('Node 3');
    $this->assertSession()->linkByHrefExists('/node/3');

    // Make sure that all elements of the table are the expected ones.
    $first_row_title = $this->xpath('//table/tbody/tr[1]/td[1]')[0];
    $this->assertEquals('Node 2', $first_row_title->getText());
    $first_row_type = $this->xpath('//table/tbody/tr[1]/td[2]')[0];
    $this->assertEquals('node', $first_row_type->getText());
    $first_row_method = $this->xpath('//table/tbody/tr[1]/td[3]')[0];
    $this->assertEquals('entity_reference', $first_row_method->getText());
    $first_row_count = $this->xpath('//table/tbody/tr[1]/td[4]')[0];
    $this->assertEquals('1', $first_row_count->getText());

    $second_row_title = $this->xpath('//table/tbody/tr[2]/td[1]')[0];
    $this->assertEquals('Node 3', $second_row_title->getText());
    $second_row_type = $this->xpath('//table/tbody/tr[2]/td[2]')[0];
    $this->assertEquals('node', $second_row_type->getText());
    $second_row_method = $this->xpath('//table/tbody/tr[2]/td[3]')[0];
    $this->assertEquals('entity_embed', $second_row_method->getText());
    $second_row_count = $this->xpath('//table/tbody/tr[2]/td[4]')[0];
    $this->assertEquals('1', $second_row_count->getText());
  }

}
