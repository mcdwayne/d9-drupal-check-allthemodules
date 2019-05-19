<?php
/**
 * Contains Notifier.php.
 */

namespace Drupal\notifier_scc\CurrencyConverterNotifier;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Egulias\EmailValidator\EmailValidator;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;

class Notifier implements NotifierInterface {

  var $configFactory;

  var $config;

  var $site_config;

  var $state;

  var $mailManager;

  var $emailValidator;

  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, MailManagerInterface $mail_manager, EmailValidator $email_validator) {
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get('notifier_scc.settings');
    $this->site_config = $this->configFactory->get('system.site');
    $this->state = $state;
    $this->mailManager = $mail_manager;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('plugin.manager.mail'),
      $container->get('email.validator')
    );
  }

  /**
   * @inheritdoc
   */
  public function notify($data) {
    $notification_email = $this->config->get('notification_email');

    if ($notification_email) {
      $last_sent = $this->state->get('notification_email_time_last_sent', 0);
      $time_interval = $this->config->get('notification_email_time');

      $result = time() > ($last_sent + $time_interval);

      if ($result) {
        $subject = 'Simple Currency Converter primary feed down using secondary';

        $body[] = 'Secondary feed responded with:';
        $body[] = 'From: ' . $data['from_currency'];
        $body[] = 'From: ' . $data['to_currency'];
        $body[] = 'Ratio: ' . $data['feed'];
        $body = implode("\n", $body);

        $this->email($subject, $body);

        $this->state->set('notification_email_time_last_sent', time());
      }
    }
  }

  private function email($subject, $body) {
    $module = 'notifier_scc';
    $key = 'admin_email';

    $to = $this->config->get('notification_email');

    $from = $this->site_config->get('mail');

    if (empty($from)) {
      $from = ini_get('sendmail_from');
    }

    $langcode = \Drupal::languageManager()->getLanguage(LanguageInterface::TYPE_CONTENT);

    $params = [
      'id' => $module . '_' . $key,
      'to' => $to,
      'subject' => $subject,
      'body' => $body,
      'headers' => [
        'From' => $from,
        'Sender' => $from,
        'Return-Path' => $from,
      ],
    ];

    $output = $this->mailManager->mail($module, $key, $to, $langcode, $params);

    return $output;
  }

}
