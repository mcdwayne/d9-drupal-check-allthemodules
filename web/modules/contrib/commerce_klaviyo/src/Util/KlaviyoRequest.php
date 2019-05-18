<?php

namespace Drupal\commerce_klaviyo\Util;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Queue\QueueFactory;

/**
 * The service for making requests to Klaviyo.
 *
 * @package Drupal\commerce_klaviyo\Util
 */
class KlaviyoRequest implements KlaviyoRequestInterface {

  use LoggerChannelTrait;

  /**
   * The Klaviyo instance.
   *
   * @var \Klaviyo
   */
  protected $klaviyo;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cron queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $cronQueue;

  /**
   * Creates new KlaviyoRequest service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, QueueFactory $queue_factory) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $public_key = $this->configFactory->get('commerce_klaviyo.settings')->get('public_key');
    $this->klaviyo = new \Klaviyo($public_key);
    $this->cronQueue = $queue_factory->get(static::CRON_QUEUE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function track($event_name, KlaviyoRequestPropertiesInterface $customer_properties, KlaviyoRequestPropertiesInterface $properties, $timestamp = NULL, $track_later = FALSE) {
    // Track later.
    if ($track_later) {
      $args = func_get_args();
      $args[4] = FALSE;
      $this->cronQueue->createQueue();
      return $this->cronQueue->createItem(['args' => $args]);
    }

    $customer_properties = $customer_properties->getProperties();

    try {
      switch ($event_name) {
        case KlaviyoRequestInterface::ORDERED_PRODUCT_EVENT:
          foreach ($properties->getOrderedProductProperties() as $order_item) {
            $order_item = $this->alterTrack($event_name, $order_item, $properties);
            $result = $this->klaviyo
              ->track($event_name, $customer_properties, $order_item, $timestamp);
            if (!$result) {
              throw new \KlaviyoException('An invalid response received from Klaviyo.');
            }
          }
          break;

        default:
          $properties = $this->alterTrack($event_name, $properties->getProperties(), $properties);
          $result = $this->klaviyo
            ->track($event_name, $customer_properties, $properties, $timestamp);
          if (!$result) {
            throw new \KlaviyoException('An invalid response received from Klaviyo.');
          }
      }
    }
    catch (\KlaviyoException $e) {
      $request = [
        'event' => $event_name,
         /*'properties' => $properties,
         'customer_properties' => $customer_properties,*/
      ];
      $this->logError($request, $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function identify(KlaviyoRequestPropertiesInterface $customer_properties) {
    try {
      $customer_properties = $customer_properties->getProperties();
      $this->klaviyo
        ->identify($this->alterIdentify($customer_properties));
    }
    catch (\KlaviyoException $e) {
      $request = [
        'event' => 'identify',
        // 'customer_properties' => $customer_properties.
      ];
      $this->logError($request, $e->getMessage());
    }
  }

  /**
   * Logs an error.
   *
   * @param array $request
   *   The request data.
   * @param string $error_message
   *   The error message.
   */
  protected function logError(array $request, $error_message) {
    $this->getLogger('commerce_klaviyo')->log('error', 'An error occurred while attempting to do a request to Klaviyo. Error: @error. Request: @request', [
      '@error' => $error_message,
      '@request' => print_r($request, TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function alterIdentify(array $customer_properties) {
    $this->moduleHandler->alter('commerce_klaviyo_identify_request', $customer_properties);
    return $customer_properties;
  }

  /**
   * {@inheritdoc}
   */
  public function alterTrack($event_name, array $properties, KlaviyoRequestPropertiesInterface $klaviyo_request_properties) {
    $context = [
      'event_name' => $event_name,
      'klaviyo_request_properties' => clone $klaviyo_request_properties,
    ];
    $this->moduleHandler->alter('commerce_klaviyo_track_request', $properties, $context);
    return $properties;
  }

}
