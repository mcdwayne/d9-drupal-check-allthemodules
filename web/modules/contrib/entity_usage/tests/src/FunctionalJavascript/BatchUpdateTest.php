<?php

namespace Drupal\Tests\entity_usage\FunctionalJavascript;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Tests for the batch update functionality.
 *
 * @package Drupal\Tests\entity_usage\FunctionalJavascript
 *
 * @group entity_usage
 */
class BatchUpdateTest extends EntityUsageJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Grant the logged-in user permission to access the batch update page.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['perform batch updates entity usage']);
  }

  /**
   * Tests the batch update.
   */
  public function testBatchUpdate() {

    $page = $this->getSession()->getPage();
    /** @var \Drupal\entity_usage\EntityUsage $usage_service */
    $usage_service = \Drupal::service('entity_usage.usage');

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
    $this->saveHtmlOutput();

    // Create node 3 also referencing node 1 in a reference field.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node 3');
    $page->fillField('field_eu_test_related_nodes[0][target_id]', 'Node 1 (1)');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('eu_test_ct Node 3 has been created.');
    $this->saveHtmlOutput();

    // Remove one of the records from the database to simulate an usage
    // non-tracked by the module.
    $usage_service->delete(1, 'node', 2, 'node');
    $usage = $usage_service->listUsage($node1);
    $this->assertEquals($usage['node'], ['3' => '1']);

    // Go to the batch update page and check the update.
    $this->drupalGet('/admin/config/entity-usage/batch-update');
    $this->assertSession()->pageTextContains('Batch Update');
    $this->assertSession()->pageTextContains('This form allows you to reset and track again all entity usages in your system.');
    $page->pressButton('Recreate entity usage statistics');
    $this->getSession()->wait(5000);
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('Recreated entity usage for');

    // Check if the resulting usage is the expected.
    $usage = $usage_service->listUsage($node1);
    $this->assertEquals($usage['node'], ['2' => '1', '3' => '1']);

  }

}
