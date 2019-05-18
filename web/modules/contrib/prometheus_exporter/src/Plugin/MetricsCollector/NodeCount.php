<?php

namespace Drupal\prometheus_exporter\Plugin\MetricsCollector;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\prometheus_exporter\Plugin\BaseMetricsCollector;
use PNX\Prometheus\Gauge;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects metrics for the total node count.
 *
 * @MetricsCollector(
 *   id = "node_count",
 *   title = @Translation("Node count"),
 *   description = @Translation("Total node count.")
 * )
 */
class NodeCount extends BaseMetricsCollector implements ContainerFactoryPluginInterface {

  /**
   * A node entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $nodeQuery;

  /**
   * UpdateStatusCollector constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The node entity query.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryInterface $query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeQuery = $query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('node')->getQuery()
    );
  }

  /**
   * Gets a count query for this metric.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query.
   */
  protected function getCountQuery() {
    return $this->nodeQuery->count();
  }

  /**
   * {@inheritdoc}
   */
  public function collectMetrics() {
    $gauge = new Gauge($this->getNamespace(), 'total', $this->getDescription());
    $gauge->set($this->getCountQuery()->execute());
    $metrics[] = $gauge;
    return $metrics;
  }

}
