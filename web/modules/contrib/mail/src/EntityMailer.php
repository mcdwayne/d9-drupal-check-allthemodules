<?php

namespace Drupal\mail;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sends email messages using an entity for the message.
 */
class EntityMailer implements EntityMailerInterface {

  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The plugin manager mail service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $pluginManagerMailBackend;

  /**
   * The plugin manager mail processor service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManagerMailProcessor;

  /**
   * Constructs a new EntityMailer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Mail\MailManagerInterface $plugin_manager_mail_backend
   *   The plugin manager mail service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager_mail_processor
   *   The plugin manager mail processor service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, MailManagerInterface $plugin_manager_mail_backend, PluginManagerInterface $plugin_manager_mail_processor) {
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
    $this->pluginManagerMailBackend = $plugin_manager_mail_backend;
    $this->pluginManagerMailProcessor = $plugin_manager_mail_processor;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(MailMessageInterface $entity, $to, $params = [], $reply = NULL) {
    $site_config = $this->configFactory->get('system.site');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    // Bundle up the variables into a structured array for altering.
    $message = array(
      'id' => $entity->id(),
      // Legacy property.
      'module' => 'mail',
      // Legacy property.
      'key' => 'none',
      'to' => $to,
      'from' => $site_mail,
      'reply-to' => $reply,
      'langcode' => $entity->language()->getId(),
      'params' => $params,
      'send' => TRUE,
      'subject' => '',
      'body' => [],
    );

    // Build the default headers.
    $headers = array(
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
    );
    // To prevent email from looking like spam, the addresses in the Sender and
    // Return-Path headers should have a domain authorized to use the
    // originating SMTP server.
    $headers['Sender'] = $headers['Return-Path'] = $site_mail;
    $headers['From'] = $site_config->get('name') . ' <' . $site_mail . '>';
    if ($reply) {
      $headers['Reply-to'] = $reply;
    }
    $message['headers'] = $headers;

    // Process the mail.
    $this->processMailMessage($entity, $to, $params, $reply);

    // Get the mail backend plugin.
    // The message entity may specify this;
    $backend_plugin_id = $entity->getMailBackendPluginID();
    if (empty($backend_plugin_id)) {
      // If the entity doesn't specify a mail backend plugin, use the default
      // from configuration.
      $configuration = $this->configFactory->get('system.mail')->get('interface');
      $backend_plugin_id = $configuration['default'];
    }

    // TODO: alter hook?

    // Put the message subject and body into the $message array for core systems
    // to work with, with the body exploded into lines.
    $message['subject'] = $entity->getSubject();
    $body = $entity->getBody();
    $message['body'] = explode("\n", $body);

    $backend_plugin = $this->pluginManagerMailBackend->createInstance($backend_plugin_id);

    // Format the message body.
    $message = $backend_plugin->format($message);

    // Send the mail.
    // TODO: implement a way for alter hooks and processors to prevent sending,
    // same as core?

    // Ensure that subject is plain text. By default translated and
    // formatted strings are prepared for the HTML context and email
    // subjects are plain strings.
    if ($message['subject']) {
      $message['subject'] = PlainTextOutput::renderFromHtml($message['subject']);
    }
    $message['result'] = $backend_plugin->mail($message);
    // Log errors.
    if (!$message['result']) {
      $this->loggerFactory->get('mail')
        ->error('Error sending email (from %from to %to with reply-to %reply).', array(
        '%from' => $message['from'],
        '%to' => $message['to'],
        '%reply' => $message['reply-to'] ? $message['reply-to'] : $this->t('not set'),
      ));
      drupal_set_message($this->t('Unable to send email. Contact the site administrator if the problem persists.'), 'error');
    }
  }

   /**
    * {@inheritdoc}
    */
  public function processMailMessage(MailMessageInterface $entity, $to, $params = [], $reply = NULL) {
    // This will typically replace tokens, and so on.
    // (This is the equivalent of hook_mail() processing the message array.)
    $processor_plugin_id = $entity->getMailProcessorPluginID();
    if (!empty($processor_plugin_id)) {
      $processor_plugin = $this->pluginManagerMailProcessor->createInstance($processor_plugin_id);

      $processor_plugin->processMessage($entity, $to, $params, $reply);
    }
    // TODO: default to a basic entity token processor?
  }

}
