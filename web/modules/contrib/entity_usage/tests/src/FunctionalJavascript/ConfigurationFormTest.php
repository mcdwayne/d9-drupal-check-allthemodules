<?php

namespace Drupal\Tests\entity_usage\FunctionalJavascript;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\node\Entity\Node;

/**
 * Tests the configuration form.
 *
 * @package Drupal\Tests\entity_usage\FunctionalJavascript
 *
 * @group entity_usage
 */
class ConfigurationFormTest extends EntityUsageJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
  ];

  /**
   * Tests the config form.
   */
  public function testConfigForm() {
    $this->drupalPlaceBlock('local_tasks_block');
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Check the form is using the expected permission-based access.
    $this->drupalGet('/admin/config/entity-usage/settings');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogin($this->drupalCreateUser([
      'bypass node access',
      'administer entity usage',
      'access entity usage statistics',
    ]));
    $this->drupalGet('/admin/config/entity-usage/settings');
    $assert_session->statusCodeEquals(200);

    // Check the form elements are there.
    $assert_session->titleEquals('Entity Usage Settings' . ' | Drupal');
    $summary = $assert_session->elementExists('css', '#edit-local-task-enabled-entity-types summary');
    $this->assertEquals('Local tasks', $summary->getText());
    $assert_session->pageTextContains('Check in which entity types there should be a tab (local task) linking to the usage page.');
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if (($entity_type instanceof ContentEntityTypeInterface) && $entity_type->hasLinkTemplate('canonical')) {
        $field_name = "local_task_enabled_entity_types[$entity_type_id]";
        $assert_session->fieldExists($field_name);
        // By default none of them should be enabled.
        $assert_session->checkboxNotChecked($field_name);
      }
    }

    // Enable it for nodes.
    $page->checkField('local_task_enabled_entity_types[node]');
    $page->pressButton('Save configuration');
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('The configuration options have been saved.');
    $assert_session->checkboxChecked('local_task_enabled_entity_types[node]');
    $node = Node::create([
      'type' => 'eu_test_ct',
      'title' => 'Test node',
    ]);
    $node->save();
    $this->drupalGet("/node/{$node->id()}");
    $assert_session->pageTextContains('Usage');
    $page->clickLink('Usage');
    $this->saveHtmlOutput();
    // We should be at /node/*/usage.
    $this->assertTrue(strpos($session->getCurrentUrl(), "/node/{$node->id()}/usage") !== FALSE);
    $assert_session->pageTextContains('Entity usage information for Test node');
    $assert_session->pageTextContains('There are no recorded usages for ');
    // We still have the local tabs available.
    $page->clickLink('View');
    $this->saveHtmlOutput();
    // We should be back at the node view.
    $assert_session->titleEquals('Test node' . ' | Drupal');
  }

}
