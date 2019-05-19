<?php

namespace Drupal\watchdog_mailer\Logger;

use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Component\Render\FormattableMarkup;

/**
 * {@inheritdoc}
 */
class WatchdogMailer implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    try {
      $config = \Drupal::config('watchdog_mailer.settings');
      if ($level <= RfcLogLevel::WARNING || ($config->get('php_notices') && $context['channel'] == 'php')) {
        $body = new FormattableMarkup($message, $context);
        $params = [
          'subject' => t('Error in the database log: @channel', ['@channel' => $context['channel']]),
          'body' => $body->__toString(),
        ];
        $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if (!\Drupal::service('plugin.manager.mail')
          ->mail('watchdog_mailer', 'watchdog_mailer', $config->get('mail_address'), $langcode, $params)
        ) {
          throw new Exception('There was a problem sending the message.');
        }
      }
    }
    catch (Exception $e) {
      // There can be no watchdog_exception to avoid a possible endless cycle.
      \Drupal::logger('watchdog_mailer')->notice($e->getMessage());
    }
  }

}
