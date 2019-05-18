<?php

namespace Drupal\purge_users\Plugin\BatchWorker;

/**
 * Class BatchWorker.
 *
 * @package Drupal\purge_users\Plugin\BatchWorker
 */
class BatchWorker {

  /**
   * Process items in a batch.
   */
  public static function batchworkerpurgeusers($account, &$context) {
    if (!isset($context['results']['purged'])) {
      $context['results']['purged'] = 0;
    }
    // Perform user deletion operation.
    $name = $account->get('name')->value;
    $mail = $account->get('mail')->value;
    $uid = $account->get('uid')->value;

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
    $message = t('Purged user: %name &lt; %mail &gt;', array(
      '%name' => $name,
      '%mail' => $mail,
    ));
    \Drupal::logger('purge_users')->notice($message);
    // Display a progress message...
    $context['message'] = "Now processing $name ...";

    // Update our progress information.
    $context['results']['purged']++;
  }

}
