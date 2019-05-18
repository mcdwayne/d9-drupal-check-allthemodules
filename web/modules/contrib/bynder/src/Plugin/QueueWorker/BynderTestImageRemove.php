<?php

namespace Drupal\bynder\Plugin\QueueWorker;

use Drupal\bynder\BynderApiInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes uploaded test images.
 *
 * @QueueWorker(
 *   id = "bynder_test_image_remove",
 *   title = @Translation("Bynder test image remove"),
 *   cron = {"time" = 60}
 * )
 */
class BynderTestImageRemove extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The minimum delay in seconds for items to wait until processed.
   *
   * @var int
   */
  const DELAY = 600;

  /**
   * Bynder api service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   *   Bynder api service.
   */
  protected $bynder;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * BynderTestImageRemove constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\bynder\BynderApiInterface $bynder
   *   The Bynder API service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    BynderApiInterface $bynder,
    TimeInterface $time
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->bynder = $bynder;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('bynder_api'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if ($data['created'] + static::DELAY <= $this->time->getRequestTime()) {
      $this->bynder->deleteMedia($data['mediaid']);
    }
    else {
      throw new RequeueException(
        'Minimum wait time has not passed for this item.'
      );
    }
  }

}
