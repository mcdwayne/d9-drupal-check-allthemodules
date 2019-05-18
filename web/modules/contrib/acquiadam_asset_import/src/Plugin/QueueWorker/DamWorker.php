<?php

namespace Drupal\acquiadam_asset_import\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\media\Entity\Media;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class DamWorker.
 *
 * @QueueWorker(
 *  id = "dam_worker",
 *  title = "Queue worker that imports new media entities from Acquia DAM",
 *  cron = {"time" = 120}
 * )
 */
class DamWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  protected $storage;

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * DamWorker constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param mixed $config
   *   The ConfigFactory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->storage = \Drupal::service('entity_type.manager')->getStorage('media');
    $this->config = $config;
  }

  /**
   * Create method.
   *
   * @param \Drupal\acquiadam_asset_import\Plugin\QueueWorker\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return \Drupal\acquiadam_asset_import\Plugin\QueueWorker\DamWorker|\Drupal\Core\Plugin\ContainerFactoryPluginInterface
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * Process a queue item.
   */
  public function processItem($item) {
    $bundle = $this->config->get('acquiadam_asset_import.config')->get('bundle');
    $entity = Media::create([
      'bundle' => $bundle,
      'uid' => 1,
      'name' => $item['name'],
      'field_acquiadam_asset_id' => [
        'value' => $item['asset_id'],
      ],
    ]);
    $entity->save();
  }

}
