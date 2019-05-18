<?php

namespace Drupal\queue_mail\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Queue\RequeueException;

/**
 * Sends emails form queue.
 *
 * @QueueWorker(
 *   id = "queue_mail",
 *   title = @Translation("Queue mail worker"),
 *   cron = {"time" = 60}
 * )
 */
class SendMailQueueWorker extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($message) {
    $original_message = $message;
    $config = \Drupal::config('queue_mail.settings');
    $interval = $config->get('requeue_interval');

    // Prevent retrying until specified interval has elapsed.
    if (isset($message['last_attempt']) && ($message['last_attempt'] + $interval) > time()) {
      throw new RequeueException();
    }
    // Retrieve the responsible implementation for this message.
    $system = \Drupal::service('plugin.manager.mail')
      ->getInstance(array('module' => $message['module'], 'key' => $message['key']));

    // Set theme that was used to generate mail body.
    $theme_manager = \Drupal::service('theme.manager');
    $current_active_theme = $theme_manager->getActiveTheme();

    if ($message['theme'] && $message['theme'] != $current_active_theme->getName()) {
      $theme_manager->setActiveTheme(\Drupal::service('theme.initialization')->initTheme($message['theme']));
    }

    // Set mail's language as active.
    $language_manager = \Drupal::languageManager();
    $language_negotiator = \Drupal::service('queue_mail.language_negotiator');
    $current_language = $language_manager->getCurrentLanguage()->getId();
    if ($message['langcode'] != $current_language) {
      $language_manager->setNegotiator($language_negotiator);
      // Needed to re-run language negotiation.
      $language_manager->reset();
      $language_manager->getNegotiator()->setLanguageCode($message['langcode']);
    }

    try {
      // Format the message body.
      $message = $system->format($message);
    }
    finally {
      // Revert the active theme, this is done inside a finally block so it is
      // executed even if an exception is thrown during sending a mail.
      if ($message['theme'] != $current_active_theme->getName()) {
        $theme_manager->setActiveTheme($current_active_theme);
      }

      // Revert the active language.
      if ($message['langcode'] != $current_language) {
        $language_manager->reset();
        $language_manager->getNegotiator()->setLanguageCode($current_language);
      }
    }

    // The original caller requested sending. Sending was canceled by one or
    // more hook_mail_alter() implementations. We set 'result' to NULL,
    // because FALSE indicates an error in sending.
    if (empty($message['send'])) {
      $message['result'] = NULL;
    }
    // Sending was originally requested and was not canceled.
    else {
      // Ensure that subject is plain text. By default translated and
      // formatted strings are prepared for the HTML context and email
      // subjects are plain strings.
      if ($message['subject']) {
        $message['subject'] = PlainTextOutput::renderFromHtml($message['subject']);
      }
      $message['result'] = $system->mail($message);

      if ($wait_time = $config->get('queue_mail_queue_wait_time')) {
        sleep($wait_time);
      }

      // Log errors.
      if (!$message['result']) {
        \Drupal::logger('mail')
          ->error('Error sending email (from %from to %to with reply-to %reply).', array(
            '%from' => $message['from'],
            '%to' => $message['to'],
            '%reply' => $message['reply-to'] ? $message['reply-to'] : t('not set'),
          ));

        $original_message['last_attempt'] = time();

        if (!isset($original_message['fail_count'])) {
          $original_message['fail_count'] = 0;
        }
        $original_message['fail_count']++;

        $threshold = $config->get('threshold');

        // Add back to the queue with an updated fail count.
        if ($original_message['fail_count'] < $threshold) {
          \Drupal::queue('queue_mail', TRUE)->createItem($original_message);
        }
        else {
          \Drupal::logger('mail')
            ->error('Attempt sending email (from %from to %to with reply-to %reply) exceeded retry threshold and was deleted.', array(
              '%from' => $message['from'],
              '%to' => $message['to'],
              '%reply' => $message['reply-to'] ? $message['reply-to'] : t('not set'),
            ));
        }
      }
    }

    return $message;
  }
}
