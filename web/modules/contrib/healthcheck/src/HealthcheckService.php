<?php

namespace Drupal\healthcheck;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Queue\QueueFactory;
use Drupal\healthcheck\Event\HealthcheckCriticalEvent;
use Drupal\healthcheck\Event\HealthcheckEvents;
use Drupal\healthcheck\Event\HealthcheckRunEvent;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Form\HealthcheckSettingsForm;
use Drupal\healthcheck\Plugin\HealthcheckPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\healthcheck\Report\Report;
use Drupal\Core\State\StateInterface;

/**
 * Class HealthcheckService.
 */
class HealthcheckService implements HealthcheckServiceInterface {

  /**
   * The Check Config service.
   *
   * @var \Drupal\healthcheck\CheckConfigServiceInterface
   */
  protected $checkConfigSrv;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The time manager.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeManager;

  /**
   * The State manager.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $event_dispatcher;

  /**
   * Constructs a new HealthcheckService object.
   */
  public function __construct(CheckConfigServiceInterface $check_config_srv,
                              ConfigFactoryInterface $config_factory,
                              QueueFactory $queue_factory,
                              TimeInterface $time_maanger,
                              StateInterface $state,
                              ContainerAwareEventDispatcher $event_dispatcher) {
    $this->checkConfigSrv = $check_config_srv;
    $this->configFactory = $config_factory;
    $this->queueFactory = $queue_factory;
    $this->timeManager = $time_maanger;
    $this->state = $state;
    $this->event_dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastTimestamp() {
    return $this->state->get('healthcheck.last_run');
  }

  /**
   * {@inheritdoc}
   */
  public function setLastTimestamp($last) {
    $this->state->set('healthcheck.last_run', $last);
  }

  /**
   * {@inheritdoc}
   */
  public function getInterval() {
    $conf = $this->configFactory->get(HealthcheckSettingsForm::CONF_ID);

    return $conf->get('run_every');
  }

  /**
   * {@inheritdoc}
   */
  public function runReport() {
    $report = new Report();

    $store = $this->configFactory->get(HealthcheckSettingsForm::CONF_ID);
    $tags = $store->get('categories');
    $omit = $store->get('omit_checks');

    /** @var \Drupal\healthcheck\Entity\CheckConfigInterface $check_config */
    foreach ($this->checkConfigSrv->getByTags($tags, $omit) as $check_config) {
      $check = $check_config->getCheck();

      // Get the findings for the check.
      $findings = $check->getFindings();

      // Add the findings to the report.
      $report->addFindings($findings);
    }

    $this->event_dispatcher->dispatch(HealthcheckEvents::CHECK_RUN, new HealthcheckRunEvent($report));

    if ($report->getHighestStatus() == FindingStatus::CRITICAL) {
      $this->event_dispatcher->dispatch(HealthcheckEvents::CHECK_CRITICAL, new HealthcheckCriticalEvent($report));
    }

    return $report;
  }

  /**
   * {@inheritdoc}
   */
  public function cron() {
    // Get the interval from config.
    $interval = $this->getInterval();
    if ($interval < 0) {
      // If the interval is less than 0, cron processing is disabled.
      return;
    }

    // What times are it?
    $now = $this->timeManager->getRequestTime();
    $then = $this->getLastTimestamp();
    $next = $then + $interval;

    // If we're due for a new cron run, queue processing.
    if ($now > $next) {
      $this->queueAll();

      // Update the last timestamp to the current request time.
      $this->setLastTimestamp($now);
    }
  }

  /**
   * Queue all reports for processing given the module configuration.
   */
  protected function queueAll() {
    // Get the cron worker.
    $queue = $this->queueFactory->get('healthcheck_cron_worker');

    $queue->createItem([
      'timestamp' => $this->timeManager->getRequestTime(),
    ]);
  }

}
