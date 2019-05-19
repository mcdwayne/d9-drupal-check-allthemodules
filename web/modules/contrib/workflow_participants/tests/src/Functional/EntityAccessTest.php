<?php

namespace Drupal\Tests\workflow_participants\Functional;

use Drupal\workflows\Entity\Workflow;

/**
 * Tests entity access for workflow participants.
 *
 * @group workflow_participants
 */
class EntityAccessTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->participants[1]);
  }

  /**
   * Test read access for editors and reviewers.
   */
  public function testReadAccess() {
    $this->node->setUnpublished();
    $this->node->save();

    // Should not have access if not a participant.
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(403);

    // Add this user as a reviewer.
    $participants = \Drupal::entityTypeManager()->getStorage('workflow_participants')->loadForModeratedEntity($this->node);
    $participants->reviewers[0] = $this->participants[1];
    $participants->save();

    $this->assertTrue($this->node->access('view'));
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test edit access.
   */
  public function testEditAccess() {
    // Should not have access if not a participant.
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Add this user as an editor.
    $participants = \Drupal::entityTypeManager()->getStorage('workflow_participants')->loadForModeratedEntity($this->node);
    $participants->editors[0] = $this->participants[1];
    $participants->save();

    // Should not have access if no accessible transitions.
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Add an accessible transition.
    /** @var \Drupal\content_moderation\ModerationStateTransitionInterface $transition */
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    $workflow->setThirdPartySetting('workflow_participants', 'editor_transitions', ['publish' => 'publish']);
    $workflow->setThirdPartySetting('workflow_participants', 'reviewer_transitions', ['publish' => 'publish']);
    $workflow->save();

    // User should now have edit access.
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(200);

    // Remove edit access, add reviewer access, and verify no edit access.
    $participants->editors = [];
    $participants->reviewers[0] = $this->participants[1];
    $participants->save();
    $this->drupalGet($this->node->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Verifies latest version tab access.
   */
  public function testLatestVersion() {
    // Create a forward revision.
    $this->node->moderation_state = 'published';
    $this->node->save();
    $this->node->moderation_state = 'draft';
    $this->node->save();
    $this->assertTrue($this->moderationInfo->hasPendingRevision($this->node));

    // There should be no access initially.
    $this->drupalGet($this->node->toUrl('latest-version'));
    $this->assertSession()->statusCodeEquals(403);

    // Add this user as a reviewer.
    $participants = \Drupal::entityTypeManager()->getStorage('workflow_participants')->loadForModeratedEntity($this->node);
    $participants->reviewers[0] = $this->participants[1];
    $participants->save();

    $this->drupalGet($this->node->toUrl('latest-version'));
    $this->assertSession()->statusCodeEquals(200);

    // There should be no moderatin ability initially.
    $this->assertSession()->fieldNotExists(t('Log message'));
    $this->assertSession()->fieldNotExists(t('Change to'));

    // Add an accessible transition.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    $workflow->setThirdPartySetting('workflow_participants', 'reviewer_transitions', ['publish' => 'publish']);
    $workflow->save();

    // Transition the node and post a log message.
    $this->drupalGet($this->node->toUrl('latest-version'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists(t('Log message'));
    $this->assertSession()->fieldExists(t('Change to'));
    $edit = [
      'new_state' => 'published',
      'revision_log' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply'));

    // Reload node.
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($this->node->id());
    $this->assertEquals($edit['revision_log'], $node->revision_log->value);
  }

}
