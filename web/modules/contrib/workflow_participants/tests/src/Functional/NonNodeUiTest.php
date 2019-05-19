<?php

namespace Drupal\Tests\workflow_participants\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Tests\workflow_participants\Kernel\WorkflowParticipantsTestTrait;
use Drupal\user\Entity\Role;
use Drupal\workflow_participants\Entity\WorkflowParticipantsInterface;

/**
 * Tests for non-node functionality.
 *
 * @group workflow_participants.
 */
class NonNodeUiTest extends TestBase {

  use WorkflowParticipantsTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a bundle.
    $type = BlockContentType::create(['id' => 'basic']);
    $type->save();

    $this->enableModeration('block_content', 'basic');

    $roles = $this->adminUser->getRoles(TRUE);
    $role = Role::load(reset($roles));
    $role->grantPermission('administer blocks');
    $role->grantPermission('use editorial transition create_new_draft');
    $role->save();
  }

  /**
   * Tests the admin UI for non-nodes.
   */
  public function testAdminUi() {
    $this->drupalLogin($this->adminUser);
    $entity = BlockContent::create(['type' => 'basic']);
    $entity->save();
    $this->drupalGet($entity->toUrl());
    $this->assertSession()->linkExists(t('Workflow participants'));
    $this->drupalGet($entity->toUrl('workflow-participants'));
    $this->assertSession()->statusCodeEquals(200);

    // Add some participants.
    $edit = [
      'editors[0][target_id]' => $this->participants[1]->getAccountName(),
      'reviewers[0][target_id]' => $this->participants[2]->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $workflow_participants = \Drupal::entityTypeManager()
      ->getStorage('workflow_participants')
      ->loadForModeratedEntity($entity);
    $this->assertInstanceOf(WorkflowParticipantsInterface::class, $workflow_participants);
    $this->assertEquals($entity->id(), $workflow_participants->getModeratedEntity()->id());
    $this->assertEquals([$this->participants[1]->id()], array_keys($workflow_participants->getEditors()));
    $this->assertEquals([$this->participants[2]->id()], array_keys($workflow_participants->getReviewers()));

  }

}
