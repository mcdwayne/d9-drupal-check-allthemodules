<?php

namespace Drupal\odoo_api_logs\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\odoo_api\Event\OdooApiCallBaseEvent;
use Drupal\odoo_api\Event\OdooApiFailedCallEvent;
use Drupal\odoo_api\Event\OdooApiSuccessCallEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OdooApiCallLogger.
 *
 * Subscribes for the Odoo API call event and log messages to the watchdog.
 */
class OdooApiCallLogger implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The key-value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The config factory services.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * OdooApiCallLogger constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key-value factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory services.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(Connection $connection, KeyValueFactoryInterface $key_value_factory, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->connection = $connection;
    $this->keyValueStore = $key_value_factory->get('odoo_api_logs_tags');
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[OdooApiSuccessCallEvent::EVENT_NAME] = ['handleSuccessOdooApiCall'];
    $events[OdooApiFailedCallEvent::EVENT_NAME] = ['handleFailedOdooApiCall'];

    return $events;
  }

  /**
   * Reacts on Success Odoo API call.
   *
   * @param \Drupal\odoo_api\Event\OdooApiSuccessCallEvent $event
   *   The event object.
   *
   * @throws \Exception
   *   An exception can be throw during writing a log to the database.
   */
  public function handleSuccessOdooApiCall(OdooApiSuccessCallEvent $event) {
    // Build a log message from event params.
    $log_message = $this->buildLogMessage($event);
    // Log an Odoo API call details.
    if (!empty($log_message['channel'])) {
      // Add a response details into log message.
      $log_message['message'] .= " <br/><b>Odoo call response:</b> <pre>@response</pre> ";
      $log_message['context']['@response'] = print_r($event->getResponse(), TRUE);

      $this->loggerFactory->get($log_message['channel'])
        ->debug($log_message['message'], $log_message['context']);
    }
  }

  /**
   * Reacts on Failed Odoo API call.
   *
   * @param \Drupal\odoo_api\Event\OdooApiFailedCallEvent $event
   *   The event object.
   *
   * @throws \Exception
   *   An exception can be throw during writing a log to the database.
   */
  public function handleFailedOdooApiCall(OdooApiFailedCallEvent $event) {
    // Build a log message from event params.
    $log_message = $this->buildLogMessage($event);
    // Log an Odoo API call details.
    if (!empty($log_message['channel'])) {
      // Add an API call exception into log message.
      $exception = $event->getException();
      if ($exception) {
        $log_message['message'] .= " <br/><b>Odoo call exception:</b> <pre>@exception</pre>";
        $log_message['context']['@exception'] = (is_object($exception) && method_exists($exception, 'getMessage'))
          ? $exception->getMessage()
          : $this->t('Undefined exception has been received from Odoo API call request.');
      }

      $this->loggerFactory->get($log_message['channel'])
        ->debug($log_message['message'], $log_message['context']);
    }
  }

  /**
   * Build a log message from event params.
   *
   * @param \Drupal\odoo_api\Event\OdooApiCallBaseEvent $event
   *   The event object.
   *
   * @return array
   *   An associative array of a log message with the following keys:
   *     channel - debug channel (string)
   *     message - debug message (string)
   *     context - debug message replacements (array).
   */
  public function buildLogMessage(OdooApiCallBaseEvent $event) {
    $log_message = [
      'channel' => '',
      'message' => '',
      'context' => [],
    ];

    // Generate Odoo API call tag.
    $tag = $event->getModelName() . ':' . $event->getModelMethod();
    // Replace dots in tag with ':' to make it usable as checkbox keys at
    // configuration page.
    $tag = str_replace('.', ':', $tag);

    // Store processed tags in keyValue service.
    $existing_tags = $this->keyValueStore->get('processed_tags', []);
    if (!in_array($tag, $existing_tags)) {
      $existing_tags[] = $tag;
      $this->keyValueStore->set('processed_tags', $existing_tags);
    }

    // Ensure that current tag isn't disabled from log processor.
    $disabled_tags = $this->configFactory->get('odoo_api_logs.config')
      ->get('disabled_tags');
    if ($disabled_tags && !empty($disabled_tags[$tag])) {
      return $log_message;
    }

    // Add a watchdog log with Odoo API call.
    $log_message['channel'] = $this->t('Odoo API Call: @tag', ['@tag' => $tag])->render();
    $log_message['message'] = implode("<br/>", [
      "<b>Odoo model name:</b> @model_name ",
      "<b>Odoo model method:</b> @model_method ",
      "<b>Odoo user:</b> @user ",
      "<b>Odoo method arguments:</b> <pre>@args</pre> ",
      "<b>Odoo named method arguments:</b> <pre>@named_args</pre> ",
      "<b>Odoo call time:</b> @time",
    ]);
    $log_message['context'] = [
      '@model_name' => $event->getModelName(),
      '@model_method' => $event->getModelMethod(),
      '@user' => $event->getOdooUser(),
      '@args' => print_r($event->getMethodArguments(), TRUE),
      '@named_args' => print_r($event->getNamedMethodArguments(), TRUE),
      '@time' => $event->getTime(),
    ];

    return $log_message;
  }

}
