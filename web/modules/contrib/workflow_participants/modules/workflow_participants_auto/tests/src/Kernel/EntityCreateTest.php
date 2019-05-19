<?php

namespace Drupal\Tests\workflow_participants_auto\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workflow_participants\Kernel\WorkflowParticipantsTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests that configured users are automatically added as participants.
 *
 * @group workflow_participants
 */
class EntityCreateTest extends WorkflowParticipantsTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflow_participants_auto'];

  /**
   * User IDs configured to be reviewers.
   *
   * @var integer[]
   */
  private $reviewers;

  /**
   * User IDs configured to be editors.
   *
   * @var integer[]
   */
  private $editors;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig('workflow_participants_auto');

    // Create 2 editors and 3 reviewers.
    for ($i = 0; $i < 5; $i++) {
      $user = $this->createUser();
      if ($i < 2) {
        $this->editors[$user->id()] = $user->id();
      }
      else {
        $this->reviewers[$user->id()] = $user->id();
      }
    }
  }

  /**
   * Tests participant addition on node create.
   */
  public function testNodeCreate() {
    // There shouldn't be any issue if automatic participants have not yet been
    // enabled for the workflow.
    $entity = EntityTestRev::create();
    $entity->save();
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    $this->assertEmpty($participants->getReviewerIds(), 'Reviewers were added to an entity with no automatic participants.');
    $this->assertEmpty($participants->getEditorIds(), 'Editors were added to an entity with no automatic participants.');

    // Add automatic reviewers/editors to the workflow and check that they get
    // applied when an appropriate entity gets created.
    $workflow = Workflow::load('editorial');
    $workflow->setThirdPartySetting('workflow_participants_auto', 'reviewers', $this->reviewers);
    $workflow->setThirdPartySetting('workflow_participants_auto', 'editors', $this->editors);
    $workflow->save();

    $entity = EntityTestRev::create();
    $entity->save();
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    $this->assertEquals($this->reviewers, $participants->getReviewerIds(), 'The reviewers were not automatically added correctly.');
    $this->assertEquals($this->editors, $participants->getEditorIds(), 'The editors were not automatically added correctly.');

    // Entity type that does not have workflow enabled.
    $type = NodeType::create(['type' => 'test']);
    $type->save();
    $node = Node::create([
      'type' => 'test',
      'title' => 'Zebra',
    ]);
    $node->save();
    $participants = $this->participantStorage->loadForModeratedEntity($node);
    $this->assertEmpty($participants->getReviewerIds(), 'Reviewers were automatically added to a node without workflow.');
    $this->assertEmpty($participants->getEditorIds(), 'Editors were automatically added to a node without workflow.');

  }

}
