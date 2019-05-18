<?php


namespace Drupal\healthcheck\Plugin\QueueWorker;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\healthcheck\Event\HealthcheckCronEvent;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Drupal\healthcheck\HealthcheckServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *   id = "healthcheck_cron_worker",
 *   title = @Translation("Healthcheck cron worker"),
 *   cron = {"time" = 600}
 * )
 */
class HealthcheckCronWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The healthcheck service.
   *
   * @var \Drupal\healthcheck\HealthcheckServiceInterface
   */
  protected $healthcheck_service;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $event_dispatcher;

  /**
   * HealthcheckWorkerBase constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
                              HealthcheckServiceInterface $healthcheck_service,
                              ContainerAwareEventDispatcher $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->healthcheck_service = $healthcheck_service;
    $this->event_dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Run the report.
    $report = $this->healthcheck_service->runReport();

    // Create a new cron event.
    $event = new HealthcheckCronEvent($report);

    // Dispatch it.
    $this->event_dispatcher->dispatch(HealthcheckEvents::CHECK_CRON, $event);
  }

}
