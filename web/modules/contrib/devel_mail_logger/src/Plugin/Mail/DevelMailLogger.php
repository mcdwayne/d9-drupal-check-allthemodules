<?php

namespace Drupal\devel_mail_logger\Plugin\Mail;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;


/**
 * Defines a mail backend that saves emails to DB.
 *
 * To enable, save a variable in settings.php (or otherwise) whose value
 * can be as simple as:
 * @code
 * $config['system.mail']['interface']['default'] = 'devel_mail_logger';
 * @endcode
 *
 * @Mail(
 *   id = "devel_mail_logger",
 *   label = @Translation("Devel DB Mail Logger"),
 *   description = @Translation("Saves Mails to DB")
 * )
 */
class DevelMailLogger implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new DevelMailLog object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
      Connection $database
    ){
     $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {

    $query = $this->database->insert('devel_mail_logger')
      ->fields(array(
        'timestamp' => REQUEST_TIME,
        'recipient' => $message['to'],
        'subject' => $message['subject'],
        'message' => json_encode($message),
      ));
    $query->execute();

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {

    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    // Convert any HTML to plain-text.
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }
}
