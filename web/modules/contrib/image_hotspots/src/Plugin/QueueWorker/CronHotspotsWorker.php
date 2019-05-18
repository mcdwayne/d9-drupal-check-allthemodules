<?php

namespace Drupal\image_hotspots\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes hotspots in queue on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_image_hotspots_deletion",
 *   title = @Translation("Cron image hotspots deletion"),
 *   cron = {"time" = 10}
 * )
 */
class CronHotspotsWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The image hotspots storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $hotspotsStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $hotspots_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->hotspotsStorage = $hotspots_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('image_hotspot')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $hotspot = $this->hotspotsStorage->load($data['hid']);
    if (!is_null($hotspot)) {
      $hotspot->delete();
    }
  }

}
