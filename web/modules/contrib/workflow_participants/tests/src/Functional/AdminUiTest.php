<?php

namespace Drupal\Tests\workflow_participants\Functional;

use Drupal\user\RoleInterface;
use Drupal\workflow_participants\Entity\WorkflowParticipantsInterface;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests for the admin UI of workflow participants.
 *
 * @group workflow_participants
 */
class AdminUiTest extends TestBase {

  /**
   * Tests admin configuration UI.
   */
  public function testAdminConfiguration() {
    // Verify 3rd-party settings work for transitions on edit form.
    $expected = [
      'archive' => 'archive',
      'create_new_draft' => 'create_new_draft',
    ];
    foreach ($expected as $transition) {
      $this->drupalGet('admin/config/workflow/workflows/manage/editorial/transition/' . $transition);
      $edit = [
        'editor_transitions' => TRUE,
        'reviewer_transitions' => $transition === 'archive',
      ];
      $this->drupalPostForm(NULL, $edit, t('Save'));
    }

    // Test that the edit form for the transitions display the saved
    // editor_transitions and reviewer_transitions settings properly.
    foreach ($expected as $transition) {
      $this->drupalGet('admin/config/workflow/workflows/manage/editorial/transition/' . $transition);
      $this->assertSession()->checkboxChecked('editor_transitions');
      if ($transition === 'archive') {
        $this->assertSession()->checkboxChecked('reviewer_transitions');
      }
      else {
        $this->assertSession()->checkboxNotChecked('reviewer_transitions');
      }
    }

    // Load the workflow and verify 3rd-party setting.
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    $this->assertEquals($expected, $workflow->getThirdPartySetting('workflow_participants', 'editor_transitions', []));
    $this->assertEquals(['archive' => 'archive'], $workflow->getThirdPartySetting('workflow_participants', 'reviewer_transitions', []));

    // Test on transition add form.
    $workflow->getTypePlugin()->addState('foo', 'Foo');
    $workflow->save();
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial/add_transition');
    $edit = [
      'label' => 'Foo',
      'id' => 'draft_foo',
      'from[draft]' => TRUE,
      'to' => 'foo',
      'editor_transitions' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $expected['draft_foo'] = 'draft_foo';
    $workflow = Workflow::load('editorial');
    $this->assertEquals($expected, $workflow->getThirdPartySetting('workflow_participants', 'editor_transitions', []));
  }

  /**
   * Test basic workflow participants.
   */
  public function testUi() {
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->linkExists(t('Workflow participants'));
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(200);

    // Add some participants.
    $edit = [
      'editors[0][target_id]' => $this->participants[1]->getAccountName(),
      'reviewers[0][target_id]' => $this->participants[2]->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $workflow_participants = \Drupal::entityTypeManager()
      ->getStorage('workflow_participants')
      ->loadForModeratedEntity($this->node);
    $this->assertInstanceOf(WorkflowParticipantsInterface::class, $workflow_participants);
    $this->assertEquals($this->node->id(), $workflow_participants->getModeratedEntity()->id());
    $this->assertEquals([$this->participants[1]->id()], array_keys($workflow_participants->getEditors()));
    $this->assertEquals([$this->participants[2]->id()], array_keys($workflow_participants->getReviewers()));

    // Add another reviewer.
    $edit = [
      'editors[0][target_id]' => $this->participants[1]->getAccountName(),
      'editors[1][target_id]' => $this->participants[3]->getAccountName(),
      'reviewers[0][target_id]' => $this->participants[2]->getAccountName(),
      'reviewers[1][target_id]' => $this->participants[4]->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $workflow_participants = $this->participantStorage->loadUnchanged($workflow_participants->id());
    $this->assertEquals([$this->participants[1]->id(), $this->participants[3]->id()], array_keys($workflow_participants->getEditors()));
    $this->assertEquals([$this->participants[2]->id(), $this->participants[4]->id()], array_keys($workflow_participants->getReviewers()));

    // Attempt to add a user that is not in the participant role.
    $edit = [
      'editors[0][target_id]' => $this->participants[5]->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains(t('There are no entities matching "@value".', ['@value' => $this->participants[5]->getAccountName()]));

    // Add a node type without moderation enabled, and verify the tab doesn't
    // appear for content of that type.
    $node_type = $this->createContentType();
    $node = $this->createNode([
      'type' => $node_type->id(),
    ]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->linkNotExists(t('Workflow participants'));
    $this->drupalGet('node/' . $node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests author access to manage participants.
   */
  public function testAuthorUi() {
    $author = $this->createUser([
      'create article content',
      'edit own article content',
      'manage own workflow participants',
    ]);
    $this->drupalLogin($author);

    // Verify they cannot access another user's workflow participants.
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(403);

    // Verify they can access their own participants.
    $node = $this->createNode(['type' => 'article', 'uid' => $author->id()]);
    $this->drupalGet('node/' . $node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test participants ability to view/edit participants.
   */
  public function testEditorUi() {
    $editor = $this->participants[2];

    // User should not have view access prior to being a participant.
    $this->assertFalse($this->node->access('view', $editor));
    $participants = $this->participantStorage->loadForModeratedEntity($this->node);
    $participants->editors[0] = $editor;
    $participants->save();

    // Editor should have full UI access.
    $this->drupalLogin($editor);
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->linkExists(t('Workflow participants'));
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'reviewers[0][target_id]' => $this->participants[3]->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $participants = $this->participantStorage->loadUnchanged($participants->id());
    $expected = [
      $this->participants[3]->id() => $this->participants[3]->id(),
    ];
    $this->assertEquals($expected, $participants->getReviewerIds());

    // Remove self and verify proper redirect.
    $edit = [
      'editors[0][target_id]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $participants = $this->participantStorage->loadUnchanged($participants->id());
    $this->assertEmpty($participants->getEditorIds());
    // For some reason, the front page redirects to the user page.
    $this->assertSession()->addressEquals($editor->toUrl()->setAbsolute()->toString());

    // Verify redirect to node page if user still has access to view.
    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($this->node->id());
    $node->setPublished();
    $node->moderation_state = 'published';
    $node->save();
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $participants->editors->target_id = $editor->id();
    $participants->save();
    // Remove self and verify proper redirect.
    $edit = [
      'editors[0][target_id]' => '',
    ];
    $this->drupalPostForm('node/' . $node->id() . '/workflow-participants', $edit, t('Save'));
    $participants = $this->participantStorage->loadUnchanged($participants->id());
    $this->assertEmpty($participants->getEditorIds());
    $this->assertSession()->addressEquals($this->node->toUrl()->setAbsolute()->toString());
    $this->assertSession()->linkNotExists(t('Workflow participants'));
  }

  /**
   * Test reviewer UI.
   */
  public function testReviewerUi() {
    $reviewer = $this->participants[1];
    $participants = $this->participantStorage->loadForModeratedEntity($this->node);
    $participants->editors[0] = $this->participants[3];
    $participants->reviewers[0] = $reviewer;
    $participants->reviewers[1] = $this->participants[2];
    $participants->save();

    // Login reviewer and verify limited access.
    $this->drupalLogin($reviewer);
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->linkExists(t('Workflow participants'));
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists(t('Remove me as reviewer'));
    $this->assertSession()->buttonExists(t('Cancel'));
    $this->assertSession()->fieldNotExists(t('Editors'));
    $this->assertSession()->fieldNotExists(t('Reviewers'));
    $this->assertSession()->linkByHrefExists($this->participants[1]->toUrl()->toString());
    $this->assertSession()->linkByHrefExists($this->participants[3]->toUrl()->toString());

    // Try cancel button.
    $this->drupalPostForm(NULL, [], t('Cancel'));
    $this->assertSession()->addressEquals('node/' . $this->node->id());

    // Remove self.
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->drupalPostForm(NULL, [], t('Remove me as reviewer'));
    $participants = $this->participantStorage->loadUnchanged($participants->id());
    $expected = [
      $this->participants[2]->id() => $this->participants[2]->id(),
    ];
    $this->assertSession()->pageTextContains(t('You have been removed as a reviewer.'));
    $this->assertEquals($expected, $participants->getReviewerIds());
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that normal authenticated users can be added as participants.
   */
  public function testAuthenticatedUsers() {
    $editor = $this->drupalCreateUser();

    // Add the appropriate permissions to the authenticated role.
    /** @var \Drupal\user\RoleInterface $role */
    $role = \Drupal::entityTypeManager()->getStorage('user_role')->load(RoleInterface::AUTHENTICATED_ID);
    $role->grantPermission('can be workflow participant');
    $role->save();

    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->linkExists(t('Workflow participants'));
    $this->drupalGet('node/' . $this->node->id() . '/workflow-participants');
    $this->assertSession()->statusCodeEquals(200);

    // Add some participants.
    $edit = [
      'editors[0][target_id]' => $editor->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $workflow_participants = \Drupal::entityTypeManager()
      ->getStorage('workflow_participants')
      ->loadForModeratedEntity($this->node);
    $this->assertInstanceOf(WorkflowParticipantsInterface::class, $workflow_participants);
    $this->assertEquals($this->node->id(), $workflow_participants->getModeratedEntity()->id());
    $this->assertEquals([$editor->id()], array_keys($workflow_participants->getEditors()));
    $this->assertEmpty($workflow_participants->getReviewers());
  }

  /**
   * Tests basic cache tag invalidation.
   */
  public function testCacheTags() {
    $author = $this->drupalCreateUser(['manage own workflow participants']);
    $this->node->setOwner($author);
    $this->node->moderation_state = 'published';
    $this->node->save();

    $editor = $this->participants[1];
    $this->drupalLogin($editor);
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkNotExists(t('Workflow participants'));

    // Login as author, add the editor.
    $this->drupalLogin($author);
    $this->drupalGet('/node/' . $this->node->id() . '/workflow-participants');
    $edit = [
      'editors[0][target_id]' => $editor->getAccountName(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalLogin($editor);
    $this->drupalGet($this->node->toUrl());
    $this->assertSession()->linkExists(t('Workflow participants'));
    $this->clickLink(t('Workflow participants'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
