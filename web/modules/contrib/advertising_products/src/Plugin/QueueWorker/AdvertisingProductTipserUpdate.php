<?php

namespace Drupal\advertising_products\Plugin\QueueWorker;

use Drupal\advertising_products\AdvertisingProductsProviderManager;
use Drupal\advertising_products\QueueBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Advertising Product entity updates.
 *
 * @QueueWorker(
 *   id = "advertising_product_tipser_provider_update",
 *   title = @Translation("Advertising product update for tipser products"),
 *   cron = {"time" = 60}
 * )
 */
class AdvertisingProductTipserUpdate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\advertising_products\AdvertisingProductsProviderManager
   */
  protected $providerManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\advertising_products\AdvertisingProductsProviderManager $providerManager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AdvertisingProductsProviderManager $providerManager, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $providerManager;
    $this->databaseConnection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.advertising_products.provider'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    list($entity_id, $product_id, $provider_id) = $data;
    // Update product data
    $provider = $this->providerManager->createInstance($provider_id);
    $provider->updateProduct($product_id, $entity_id);
  }
}
