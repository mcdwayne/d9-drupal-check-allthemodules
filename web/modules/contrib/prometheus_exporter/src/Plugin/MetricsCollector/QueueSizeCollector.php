<?php

namespace Drupal\prometheus_exporter\Plugin\MetricsCollector;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\prometheus_exporter\Plugin\BaseMetricsCollector;
use PNX\Prometheus\Gauge;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects metrics for queue sizes.
 *
 * @MetricsCollector(
 *   id = "queue_size",
 *   title = @Translation("Queue size"),
 *   description = @Translation("Provides metrics for queue sizes.")
 * )
 */
class QueueSizeCollector extends BaseMetricsCollector implements ContainerFactoryPluginInterface {

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * UpdateStatusCollector constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueueFactory $queueFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collectMetrics() {
    $metrics = [];

    $queues = $this->getQueues();

    foreach ($queues as $queueName) {
      $queue = $this->queueFactory->get($queueName);
      $size = $queue->numberOfItems();
      $gauge = new Gauge($this->getNamespace(), 'total', $this->getDescription());
      $gauge->set($size, ['queue' => $queueName]);
      $metrics[] = $gauge;
    }

    return $metrics;
  }

  /**
   * Gets a list of queue names to collect.
   *
   * @return string[]
   *   The queue names.
   */
  protected function getQueues() {
    // TODO work out how to store and retrieve queues.
    $settings = $this->getConfiguration()['settings'];
    if (isset($settings['queues'])) {
      return $settings['queues'];
    }
    return ['queue'];
  }

}
