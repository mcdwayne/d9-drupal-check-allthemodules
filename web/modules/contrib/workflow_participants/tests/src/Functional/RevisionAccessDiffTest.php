<?php

namespace Drupal\Tests\workflow_participants\Functional;

/**
 * Tests revision access with the Diff module enabled.
 *
 * @group workflow_participants
 *
 * @requires module diff
 */
class RevisionAccessDiffTest extends RevisionAccessTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['diff'];

  /**
   * Tests ability to view diffs of revisions.
   */
  public function testRevisionDiffs() {
    // Create a forward revision.
    $this->node->moderation_state = 'published';
    $this->node->save();
    $this->node->moderation_state = 'draft';
    $this->node->save();
    $this->assertTrue($this->moderationInfo->hasPendingRevision($this->node));

    // Add this user as a reviewer.
    $participants = \Drupal::entityTypeManager()->getStorage('workflow_participants')->loadForModeratedEntity($this->node);
    $participants->reviewers[0] = $this->participants[1];
    $participants->save();

    $this->drupalGet($this->node->toUrl('version-history'));
    $this->assertSession()->statusCodeEquals(200);

    $compare = [
      'radios_left' => 1,
      'radios_right' => 3,
    ];
    $this->drupalPostForm(NULL, $compare, t('Compare selected revisions'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
