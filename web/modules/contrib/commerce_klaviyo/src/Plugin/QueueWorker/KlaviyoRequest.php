<?php

namespace Drupal\commerce_klaviyo\Plugin\QueueWorker;

use Drupal\commerce_klaviyo\Util\KlaviyoRequestInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends delayed Klaviyo tracking events.
 *
 * @QueueWorker(
 *   id = "klaviyo_request",
 *   title = @Translation("Klaviyo requests"),
 *   cron = {"time" = 60}
 * )
 */
class KlaviyoRequest extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Klaviyo request service.
   *
   * @var \Drupal\commerce_klaviyo\Util\KlaviyoRequestInterface
   */
  protected $klaviyo;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, KlaviyoRequestInterface $klaviyo) {
    $this->klaviyo = $klaviyo;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data['args'])) {
      call_user_func_array([$this->klaviyo, 'track'], $data['args']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_klaviyo.klaviyo_request')
    );
  }

}
