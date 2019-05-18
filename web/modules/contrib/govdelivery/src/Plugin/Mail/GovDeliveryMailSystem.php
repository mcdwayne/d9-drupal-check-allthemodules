<?php

namespace Drupal\govdelivery\Plugin\Mail;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Modify the drupal mail system to use smtp when sending emails.
 * Include the option to choose between plain text or HTML
 *
 * borrowed heavily from SMTPMailSystem.php (SMTP module)
 *
 * @Mail(
 *   id = "GovDeliveryMailSystem",
 *   label = @Translation("GovDelivery Targeted Messaging System"),
 *   description = @Translation("Sends the message, using GovDelivery TMS.")
 * )
 */
class GovDeliveryMailSystem implements MailInterface, ContainerFactoryPluginInterface {
  protected $gdtmsConfig;
  protected $AllowHtml;

  /**
   * Logger
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a GovDeliveryMailSystem object.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger) {
    $this->gdtmsConfig = \Drupal::config('govdelivery.tms_settings');
    $this->logger = $logger;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('logger.factory'));
  }

  /**
   * Concatenate and wrap the e-mail body for either
   * plain-text or HTML emails.
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

  /**
   * Format mail.
   *
   * @param array $message
   *   Message data.
   *
   * @return bool
   *   Mail is processed.
   */
  public function mail(array $message) {
    return $this->send_message($message);
  }

  /**
   * Make the actual connecion to the govdelivery server and send the message
   */
  protected function send_message($message) {
    $sendsuccess = FALSE;

    # Get list of recipients.
    $recipients = array();
    if (is_array($message["to"])) {
      foreach ($message["to"] as $address) {
        $tolist = explode(',', $address);
        if (is_array($tolist)) {
          $filtered_list = array_map("govdelivery_filter_email", $tolist);
          $recipients = array_merge($recipients, $filtered_list);
        }
        else {
          $recipients[] = govdelivery_filter_email($tolist);
        }
      }
    }
    else {
      $tolist = explode(',', $message['to']);
      if (is_array($tolist)) {
        $filtered_list = array_map('govdelivery_filter_email', $tolist);
        $recipients = array_merge($recipients, $filtered_list);
      }
      else {
        $recipients[] = govdelivery_filter_email($tolist);
      }
    }

    $from_address = \Drupal::config('system.site')->get('name');
    if ($this->gdtmsConfig->get('override_from')) {
      if (!empty($message['from'])) {
        $from_address = $message['from'];
      }
    }

    $message_data = array(
      'body' => is_array($message['body']) ? implode("\n", $message['body']) : $message['body'],
      'from_name' => $from_address,
      'subject' => $message["subject"],
      'recipients' => array()
    );
    foreach ($recipients as $recipient) {
      $message_data['recipients'][] = array('email' => $recipient);
    }

    if ($this->gdtmsConfig->get('queue')) {
      // new messages need to be queued
      $queue = \Drupal::queue('govdelivery_tms_mailsystem', TRUE);
      $queue->createQueue();
      $queue->createItem($message_data);
      return TRUE;
    }
    // no queuing, unless fail
    if (!govdelivery_send_message($message_data)) {
      // failed to send the message immediately, queue the message
      $queue = \Drupal::queue('govdelivery_tms_mailsystem', TRUE);
      $queue->createQueue();
      $queue->createItem($message_data);
    }
  }
}
