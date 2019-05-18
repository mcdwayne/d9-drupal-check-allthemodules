<?php

namespace Drupal\purge_users\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Processes cron queue.
 *
 * @QueueWorker(
 *   id = "purge_users",
 *   title = @Translation("Purge Users Tasks Worker: Purge Users"),
 *   cron = {"time" = 15}
 * )
 */
class PurgeUsersQueueWorker extends QueueWorkerBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function processItem($account) {
    $name = $account->get('name')->value;
    $mail = $account->get('mail')->value;
    $uid = $account->get('uid')->value;

    // Send a notification email.
    $config = \Drupal::config('purge_users.settings');
    $send_notification = $config->get('send_email_notification');
    $method = $config->get('purge_user_cancel_method');
    if ($method != 'user_cancel_delete') {
      // Allow modules to add further sets to this batch.
      $handler = \Drupal::moduleHandler();
      $handler->invokeAll('user_cancel', array(array(), $account, $method));
    }
    if ($send_notification == 1) {
      purge_users_send_notification_email($account);
    }
    user_delete($uid);
    // Log purged user.
    $message = $this->t('Purged user: %name &lt; %mail &gt;', array(
      '%name' => $name,
      '%mail' => $mail,
    ));
    \Drupal::logger('purge_users')->notice($message);
  }

}
