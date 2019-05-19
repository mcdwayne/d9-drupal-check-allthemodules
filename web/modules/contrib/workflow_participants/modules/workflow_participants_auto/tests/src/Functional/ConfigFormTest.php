<?php

namespace Drupal\Tests\workflow_participants_auto\Functional;

use Drupal\Tests\workflow_participants\Functional\TestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests for the UI of automatic participants form.
 *
 * @group workflow_participants
 */
class ConfigFormTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflow_participants_auto'];

  /**
   * Tests configuration UI.
   */
  public function testConfiguration() {
    // Verify that the new tab is there.
    $this->drupalGet('admin/config/workflow/workflows/manage/editorial');
    $this->assertSession()->linkExists('Automatic participants');

    // Test that the form saves correctly.
    $editor = $this->participants[1];
    $edit = [
      'editors' => $editor->getAccountName() . ' (' . $editor->id() . ')',
      'reviewers' => '',
    ];
    $this->drupalPostForm('admin/config/workflow/workflows/manage/editorial/participants', $edit, t('Save'));
    $workflow = Workflow::load('editorial');
    $this->assertEquals([$editor->id()], $workflow->getThirdPartySetting('workflow_participants_auto', 'editors', []));
    $this->assertEquals([], $workflow->getThirdPartySetting('workflow_participants_auto', 'reviewers', []));
  }

}
