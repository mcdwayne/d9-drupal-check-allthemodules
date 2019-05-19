<?php

namespace Drupal\Tests\workflow_participants\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Test entity deletion.
 *
 * @group workflow_participants
 */
class EntityDeleteTest extends WorkflowParticipantsTestBase {

  use NodeCreationTrait;

  /**
   * Tests that corresponding participants are removed when entity is deleted.
   */
  public function testEntityDeletion() {
    $entity = EntityTestRev::create([
      'moderation_state' => 'draft',
    ]);
    $entity->save();

    // Add participants.
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    $participants->save();

    // Delete the node.
    $entity->delete();
    $this->participantStorage->resetCache();
    $this->assertNull($this->participantStorage->load($participants->id()));
  }

}
