<?php

namespace Drupal\dropshark\Collector;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CollectorManager.
 */
class CollectorManager extends DefaultPluginManager implements CollectorManagerInterface {

  /**
   * DropShark queue handling service.
   *
   * @var \Drupal\dropshark\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a DropSharkCollectorManager object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(ContainerInterface $container) {
    parent::__construct(
      'Plugin/DropShark/Collector',
      $container->get('container.namespaces'),
      $container->get('module_handler'),
      'Drupal\dropshark\Collector\CollectorInterface',
      'Drupal\dropshark\Collector\Annotation\DropSharkCollector'
    );
    $this->alterInfo('dropshark_collector_info');
    $this->setCacheBackend($container->get('cache.discovery'), 'dropshark_collectors');
    $this->factory = new ContainerFactory($this->getDiscovery(), CollectorInterface::class);
    $this->queue = $container->get('dropshark.queue');
  }

  /**
   * {@inheritdoc}
   */
  public function collect(array $events, array $data = [], $immediate = FALSE) {
    foreach ($this->getDefinitions() as $pluginId => $definition) {
      /** @var \Drupal\dropshark\Collector\Annotation\DropSharkCollector $definition */
      if (in_array('all', $events) || array_intersect($events, $definition->events)) {
        /** @var \Drupal\dropshark\Collector\CollectorInterface $plugin */
        $plugin = $this->createInstance($pluginId);
        $plugin->collect($data);
      }
    }

    if ($immediate) {
      $this->queue->setImmediateTransmit();
    }
  }

}
