<?php

namespace Drupal\govuk_notify\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\govuk_notify\NotifyService\NotifyServiceInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Defines the GovUK Notify mail backend.
 *
 * @Mail(
 *   id = "govuk_notify_mail",
 *   label = @Translation("GOV UK Notify mailer"),
 *   description = @Translation("Sends an email using the GOV UK Notify service.")
 * )
 */
class GovUKNotifyMail implements MailInterface, ContainerFactoryPluginInterface {

  private $notifyService;
  private $emailValidator;

  /**
   * Create the GovUK notify API client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NotifyServiceInterface $notify_service, EmailValidator $email_validator) {
    $this->notifyService = $notify_service;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('govuk_notify.notify_service'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Ensures that the message contains the required parameters, namely
   * template_id - the template id to use
   * params - to contain the placeholders.
   */
  public function format(array $message) {
    $config = \Drupal::config('govuk_notify.settings');

    if ($this->isEmail($message['to'])) {
      $default_template_id = $config->get('default_template_id');
    }
    else {
      $default_template_id = $config->get('default_sms_template_id');
    }

    if (empty($message['template_id'])) {
      $message['template_id'] = $default_template_id;
    }

    if (empty($message['params'])) {
      $message['params'] = [];
    }

    // @todo Duplicate for permanent failure.
    // @todo Get email address from config.
    if ($config->get('force_temporary_failure')) {
      $temporary_email = 'temp-fail@simulator.notify';
      \Drupal::logger('govuk_notify')->notice('Forcing use of email address @email', ['@email' => $temporary_email]);
      $message['to'] = $temporary_email;
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   *
   * This submits (NB not necessarily the same as send) a message to GovUK
   * Notify.
   */
  public function mail(array $message) {
    $response = FALSE;

    $template = $this->notifyService->getTemplate($message['template_id']);
    if (empty($message['params']['subject']) && !empty($message['subject']) && isset($template['subject'])
      && $this->notifyService->checkReplacement($template['subject'], 'subject')) {
      $message['params']['subject'] = $message['subject'];
    }
    // If we don't have a body set, get the default drupal body param.
    if (empty($message['params']['message']) && !empty($message['body']) && isset($template['body'])
      && $this->notifyService->checkReplacement($template['body'], 'message')) {
      $message['params']['message'] = current($message['body']);
    }

    // If we're using the default GovUK template then we should ensure that our
    // params contain 'subject' and 'message' keys. Otherwise we should just
    // check for 'to', 'template_id' or 'params' just being present.
    $warning_message = "";

    $config = \Drupal::config('govuk_notify.settings');

    if (empty($message['to']) || empty($message['template_id']) || (empty($message['params']) && !is_array($message['params']))) {
      $warning_message = "Missing one of the required parameters, 'to', 'template_id' or 'params'";
    }
    elseif ($config->get('default_template_id') == $message['template_id']) {
      if (empty($message['params']['subject']) || empty($message['params']['message'])) {
        $warning_message = "Using default template, but not supplying 'subject' or 'message' in params";
      }
    }

    if (empty($warning_message)) {
      if ($this->isEmail($message['to'])) {
        $sendMethod = 'sendEmail';
      }
      else {
        $sendMethod = 'sendSMS';
      }
      $response = $this->notifyService->$sendMethod(
        $message['to'],
        $message['template_id'],
        $message['params']
      );
    }
    else {
      \Drupal::logger('govuk_notify')->warning('Message was not submitted to GovUK Notify - @warning_message', ['@warning_message' => $warning_message]);
    }

    return $response;

  }

  /**
   * Identifies whether or not an address is an email.
   *
   * The implication is that if this is not an email address
   * then the recipient is an SMS.
   *
   * @param string $recipient
   *   An address to validate as an email.
   *
   * @return bool
   *   TRUE if an email address, else FALSE.
   */
  protected function isEmail($recipient) {
    return $this->emailValidator->isValid($recipient);
  }

}
