<?php

namespace Drupal\Tests\workflows_field\Kernel;

use Drupal\node\Entity\Node;

/**
 * Tests the field constraints.
 *
 * @group workflows_field
 */
class WorkflowsFieldContraintTest extends WorkflowsTestBase {

  /**
   * @covers \Drupal\workflows_field\Plugin\Validation\Constraint\WorkflowsFieldContraint
   * @covers \Drupal\workflows_field\Plugin\Validation\Constraint\WorkflowsFieldContraintValidator
   */
  public function testValidTransitionsNoViolations() {
    $this->container->set('current_user', $this->createUser([
      'use bureaucracy_workflow transition approved_project',
      'use bureaucracy_workflow transition ready_for_planning',
    ]));

    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    // Same state does not cause a violation.
    $node->field_status->value = 'in_discussion';
    $violations = $node->validate();
    $this->assertCount(0, $violations);

    // A valid state does not cause a violation.
    $node->field_status->value = 'approved';
    $violations = $node->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Test we can not apply invalid transitions.
   */
  public function testInvalidTransition() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    // Violation exists during invalid transition.
    $node->field_status->value = 'planning';
    $violations = $node->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('No transition exists to move from <em class="placeholder">in_discussion</em> to <em class="placeholder">planning</em>.', $violations[0]->getMessage());
  }

  /**
   * Test we cannot apply a valid transition unless we have permission.
   */
  public function testNotAllowedTransition() {
    $node = Node::create([
      'title' => 'Foo',
      'type' => 'project',
      'field_status' => 'in_discussion',
    ]);
    $node->save();

    $node->field_status->value = 'approved';
    $violations = $node->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('You do not have sufficient permissions to use the <em class="placeholder">Approved Project</em> transition.', $violations[0]->getMessage());
  }

}
