<?php

namespace Drupal\concurrent_users_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\concurrent_users_notification\DbStorage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SaveRecordToDBController.
 *
 * @package Drupal\concurrent_users_notification\Controller
 */
class SaveRecordToDBController extends ControllerBase {

  /**
   * Function for immediate notifcation.
   *
   * @param int $con_user
   *    The user information.
   */
  protected function immediateNotification($con_user) {
    $config = $this->config('concurrent_users_notification.conusersnoticonfig');
    $to = $config->get('email_id');
    $subject = $config->get('subject') . $con_user;
    $message = $config->get('message');
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'concurrent_users_notification';
    $key = 'immediate_notifications';
    $params['message'] = $message;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    if ($result['result'] !== TRUE) {
      \Drupal::logger('concurrent_users_notification')->notice('There was a problem sending your message and it was not sent.');
    }
    else {
      \Drupal::logger('concurrent_users_notification')->notice('Mail sent.');
    }
  }

  /**
   * Saverecord.
   *
   * @return string
   *   Return Hello string.
   */
  public function saveRecord() {
    $config = $this->config('concurrent_users_notification.conusersnoticonfig');
    $current_date = date('d-m-Y');
    $clc = DbStorage::loadSessionCount();
    if ($clc >= $config->get('concurrent_critical_users_count')) {
      $existRecord = DbStorage::load($current_date);
      if (isset($existRecord[0]) && $existRecord[0] < $clc) {
        $entry = array(
          'concurrent_logins_date' => $current_date,
          'concurrent_logins_count' => $clc,
        );
        DbStorage::update($entry);
        if ($config->get('enable_notification_mail')) {
          $this->immediateNotification($clc);
        }
      }
      elseif (!isset($existRecord[0])) {
        $entry = array(
          'concurrent_logins_date' => $current_date,
          'concurrent_logins_count' => $clc,
        );
        DbStorage::insert($entry);
        if ($config->get('enable_notification_mail')) {
          $this->immediateNotification($clc);
        }
      }
    }
    // HTTP 204 is "No content", meaning "I did what you asked and we're done.".
    return new Response('', 204);
  }

}
