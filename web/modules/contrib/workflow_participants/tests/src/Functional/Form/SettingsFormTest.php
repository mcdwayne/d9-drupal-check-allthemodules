<?php

namespace Drupal\Tests\workflow_participants\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Settings form test.
 *
 * @group workflow_participants
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflow_participants'];

  /**
   * Test the settings form submits properly.
   */
  public function testForm() {
    $admin = $this->createUser([
      'administer workflow participants',
      'access administration pages',
    ]);
    $this->drupalLogin($admin);

    $this->drupalGet('/admin/config/workflow');
    $this->assertSession()->linkExists(t('Workflow participant settings'));
    $this->clickLink(t('Workflow participant settings'));

    $edit = [
      'enable_notifications' => FALSE,
      'participant_message[subject]' => '',
      'participant_message[body][value]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertFalse(\Drupal::config('workflow_participants.settings')->get('enable_notifications'));
    $this->assertEmpty(\Drupal::config('workflow_participants.settings')->get('participant_message.subject'));
    $this->assertEmpty(\Drupal::config('workflow_participants.settings')->get('participant_message.body.value'));

    // Enable notifications.
    $edit = [
      'enable_notifications' => TRUE,
      'participant_message[subject]' => 'Test subject',
      'participant_message[body][value]' => 'Test body [with:token].',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertTrue(\Drupal::config('workflow_participants.settings')->get('enable_notifications'));
    $this->assertEquals('Test subject', \Drupal::config('workflow_participants.settings')->get('participant_message.subject'));
    $this->assertEquals('Test body [with:token].', \Drupal::config('workflow_participants.settings')->get('participant_message.body.value'));

    // Attempt to submit with notifications enabled, but no message body or
    // subject should result in an error.
    $edit = [
      'enable_notifications' => TRUE,
      'participant_message[subject]' => '',
      'participant_message[body][value]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('Email subject is required.'));
    $this->assertSession()->pageTextContains(t('Email body is required.'));

  }

}
