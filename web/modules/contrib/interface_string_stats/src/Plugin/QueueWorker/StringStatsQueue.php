<?php

namespace Drupal\interface_string_stats\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\interface_string_stats\StringRequestProcessor;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Interface string stats queue worker.
 *
 * @QueueWorker(
 *   id = "interface_string_stats",
 *   title = @Translation("Interface string stats"),
 *   cron = {"time" = 20}
 * )
 */
class StringStatsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * StringRequestProcessor object.
   *
   * @var \Drupal\interface_string_stats\StringRequestProcessor
   */
  protected $stringProcessor;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StringRequestProcessor $stringRequestProcessor) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringProcessor = $stringRequestProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('interface_string_stats.string_request_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->stringProcessor->processStringRequest(
      $data['language'],
      $data['string'],
      $data['context']
    );
  }

}
