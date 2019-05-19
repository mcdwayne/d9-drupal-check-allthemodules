<?php

namespace Drupal\Tests\workflow_participants\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\simpletest\UserCreationTrait;

/**
 * Integration test for the state transition validator.
 *
 * @group workflow_participants
 */
class StateTransitionValidationTest extends WorkflowParticipantsTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);

    // Dummy super user 1.
    $this->createUser();
  }

  /**
   * Test the state transition validator.
   */
  public function testStateTransitionValidation() {
    // Add a user with no permissions to transition.
    $account = $this->createUser();

    // Entity not in moderation, but with an ID. Shouldn't ever happen, but on
    // sites with existing content, it can.
    $entity = EntityTestRev::create();
    $entity->save();

    $validator = \Drupal::service('workflow_participants.state_transition_validation');
    $this->assertEmpty($validator->getValidTransitions($entity, $account));
  }

}
