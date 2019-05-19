<?php

namespace Drupal\tracdelight\Plugin\QueueWorker;
use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\tracdelight\Tracdelight;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Entity Update Tasks for My Module.
 *
 * @QueueWorker(
 *   id = "tracdelight_product_updates",
 *   title = @Translation("Tracdelight product update"),
 *   cron = {"time" = 60}
 * )
 */
class ProductUpdate  extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  protected $tracdelightService;
  protected $databaseConnection;

  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Tracdelight $tracdelight, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->tracdelightService = $tracdelight;

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
      $container->get('tracdelight'),
      $container->get('database')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    $active_products = $this->tracdelightService->queryProducts(array('EIN' => implode(',', $data)));

    $active_products_eins = array_keys($active_products);

    $this->tracdelightService->importProducts($active_products);

    $inactive_products = array_diff(array_values($data), array_values($active_products_eins));

    if ($inactive_products) {

      $this->databaseConnection->update('product')->fields(array(
        'active' => 0,
      ))->condition('ein', $inactive_products, 'IN')->execute();
    }
  }
}
