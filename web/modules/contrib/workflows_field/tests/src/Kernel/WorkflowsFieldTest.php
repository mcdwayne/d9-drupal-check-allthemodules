<?php

namespace Drupal\Tests\workflows_field\Kernel;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\workflows\Entity\Workflow;
use Drupal\workflows_field\Plugin\Field\FieldType\WorkflowsFieldItem;

/**
 * Test the workflows field.
 *
 * @group workflows_field
 */
class WorkflowsFieldTest extends WorkflowsTestBase {

  /**
   * Test the implementation of OptionsProviderInterface.
   */
  public function testOptionsProvider() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    $this->assertEquals([
      'implementing' => 'Implementing',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'planning' => 'Planning',
    'in_discussion' => 'In Discussion',
    ], $node->field_status[0]->getPossibleOptions());
    $this->assertEquals([
      'approved' => 'Approved',
      'rejected' => 'Rejected',
      'in_discussion' => 'In Discussion'
    ], $node->field_status[0]->getSettableOptions());

    $this->assertEquals([
      'implementing',
      'approved',
      'rejected',
      'planning',
      'in_discussion',
    ], $node->field_status[0]->getPossibleValues());
    $this->assertEquals([
      'approved',
      'rejected',
      'in_discussion',
    ], $node->field_status[0]->getSettableValues());
  }

  /**
   * Settable options are filtered by the users permissions.
   */
  public function testOptionsProviderFilteredByUser() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    // If a user has no permissions then the only available state is the current
    // state.
    $this->assertEquals([
      'in_discussion' => 'In Discussion',
    ], $node->field_status[0]->getSettableOptions($this->createUser()));

    // Grant the ability to use the approved_project transition and the user
    // should now be able to set the Approved state.
    $this->assertEquals([
      'in_discussion' => 'In Discussion',
      'approved' => 'Approved',
    ], $node->field_status[0]->getSettableOptions($this->createUser(['use bureaucracy_workflow transition approved_project'])));
  }

  /**
   * @covers \Drupal\workflows_field\Plugin\Field\FieldType\WorkflowsFieldItem
   */
  public function testFieldType() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    // Test the dependencies calculation.
    $this->assertEquals([
      'config' => [
        'workflows.workflow.bureaucracy_workflow',
      ],
    ], WorkflowsFieldItem::calculateStorageDependencies($node->field_status->getFieldDefinition()->getFieldStorageDefinition()));

    // Test the getWorkflow method.
    $this->assertEquals('bureaucracy_workflow', $node->field_status[0]->getWorkflow()->id());
  }

  /**
   * @covers \Drupal\workflows_field\Plugin\WorkflowType\WorkflowsField
   */
  public function testWorkflowType() {
    // Test the initial state based on the config, despite the state weights.
    $type = Workflow::load('bureaucracy_workflow')->getTypePlugin();
    $this->assertEquals('in_discussion', $type->getInitialState()->id());
  }

}
