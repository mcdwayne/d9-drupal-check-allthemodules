<?php

namespace Drupal\social_feed_fetcher\Plugin\QueueWorker;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\social_feed_fetcher\PluginNodeProcessorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SocialPostQueueWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\social_feed_fetcher\PluginNodeProcessorManager
   */
  public $nodeProcessor;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginNodeProcessorManager $nodeProcessor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->nodeProcessor = $nodeProcessor;
  }

  public function processItem($data){}

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.node_processor'));
  }
}