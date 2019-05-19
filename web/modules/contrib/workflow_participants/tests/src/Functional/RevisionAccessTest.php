<?php

namespace Drupal\Tests\workflow_participants\Functional;

/**
 * Tests access to the revision and revision history.
 *
 * @group workflow_participants
 *
 * Any tests run from within here are also run by the RevisionAccessDiffTest,
 * which extends this one.
 */
class RevisionAccessTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->participants[1]);
  }

  /**
   * Verify access to the revisions tab.
   */
  public function testRevisionsTab() {
    // There should be no access initially.
    $this->drupalGet($this->node->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($this->node->toUrl('revision'));
    $this->assertSession()->statusCodeEquals(403);

    // Add this user as a reviewer.
    $participants = \Drupal::entityTypeManager()->getStorage('workflow_participants')->loadForModeratedEntity($this->node);
    $participants->reviewers[0] = $this->participants[1];
    $participants->save();

    // There should still be no access granted to the revision tab as this node
    // has no more than one revision.
    $this->drupalGet($this->node->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(403);

    // Create a revision (node still unpublished).
    $this->node->moderation_state = 'draft';
    $this->node->save();

    // The reviewer should now have access.
    $this->drupalGet($this->node->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->node->toUrl('revision'));
    $this->assertSession()->statusCodeEquals(200);

    // Create a forward revision.
    $this->node->moderation_state = 'published';
    $this->node->save();
    $this->node->moderation_state = 'draft';
    $this->node->save();
    $this->assertTrue($this->moderationInfo->hasPendingRevision($this->node));

    // The reviewer should now have access.
    $this->drupalGet($this->node->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->node->toUrl('revision'));
    $this->assertSession()->statusCodeEquals(200);

    // Verify the default access still works for users with appropriate
    // permissions.
    $admin = $this->createUser(['view all revisions', 'view any unpublished content']);
    $this->drupalLogin($admin);
    $this->drupalGet($this->node->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($this->node->toUrl('revision'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
