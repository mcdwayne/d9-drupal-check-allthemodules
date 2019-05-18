<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\QueueWorker\SubmissionProcessing.
 */

namespace Drupal\collect\Plugin\QueueWorker;

use Drupal\collect\Event\CollectEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Queue worker for triggering post-processing of new Collect submissions.
 *
 * @see \Drupal\collect\Plugin\rest\resource\CollectResource
 *
 * @QueueWorker(
 *   id = "collect_processing",
 *   label = @Translation("Collect submission post-processing."),
 *   cron = {"time" = 60}
 * )
 */
class SubmissionProcessing extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The injected event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a SubmissionProcessing object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('event_dispatcher'));
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $this->eventDispatcher->dispatch(CollectEvent::NAME, new CollectEvent($data));
  }

}
