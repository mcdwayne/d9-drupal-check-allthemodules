<?php

namespace Drupal\Tests\workflow_participants\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\content_moderation_notifications\Entity\ContentModerationNotification;
use Drupal\user\Entity\Role;

/**
 * Tests admin UI with content moderation notifications enabled.
 *
 * @group workflow_participants
 *
 * @requires module content_moderation_notifications
 */
class AdminUiNotificationsTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['content_moderation_notifications'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    foreach ($this->adminUser->getRoles(TRUE) as $role_id) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = Role::load($role_id);
      $role->grantPermission('administer content moderation notifications');
      $role->save();
    }
  }

  /**
   * Tests 3rd-party settings on the content moderation notifications form.
   */
  public function testNotifications() {
    // Add a notification.
    $this->drupalGet('admin/config/workflow/notifications/add');
    $edit = [
      'id' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'transitions[create_new_draft]' => TRUE,
      'transitions[archive]' => TRUE,
      'workflow_participants[editors]' => TRUE,
      'workflow_participants[reviewers]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Create Notification'));

    /** @var \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $notification */
    $notification = ContentModerationNotification::load($edit['id']);
    $this->assertTrue($notification->getThirdPartySetting('workflow_participants', 'editors', FALSE));
    $this->assertTrue($notification->getThirdPartySetting('workflow_participants', 'reviewers', FALSE));

    $edit = [
      'workflow_participants[editors]' => FALSE,
    ];
    $this->drupalGet($notification->toUrl('edit-form'));
    $this->drupalPostForm(NULL, $edit, t('Update Notification'));
    $notification = ContentModerationNotification::load($notification->id());
    $this->assertFalse($notification->getThirdPartySetting('workflow_participants', 'editors', FALSE));
    $this->assertTrue($notification->getThirdPartySetting('workflow_participants', 'reviewers', FALSE));

    $edit = [
      'workflow_participants[reviewers]' => FALSE,
    ];
    $this->drupalGet($notification->toUrl('edit-form'));
    $this->drupalPostForm(NULL, $edit, t('Update Notification'));
    $notification = ContentModerationNotification::load($notification->id());
    $this->assertFalse($notification->getThirdPartySetting('workflow_participants', 'editors', FALSE));
    $this->assertFalse($notification->getThirdPartySetting('workflow_participants', 'reviewers', FALSE));

  }

}
