<?php

namespace Drupal\dashboard_connector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\dashboard_connector\Checker\CheckerInterface;

/**
 * Builds a snapshot of checks.
 */
class SnapshotBuilder implements SnapshotBuilderInterface {

  /**
   * The dashboard config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * An array of checkers.
   *
   * @var \Drupal\dashboard_connector\Checker\CheckerInterface[]
   */
  protected $checkers = [];

  /**
   * SnapshotBuilder constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('dashboard_connector.settings');
  }

  /**
   * Adds the checker from the service_collector.
   *
   * @param \Drupal\dashboard_connector\Checker\CheckerInterface $checker
   *   The checker to add.
   */
  public function addChecker(CheckerInterface $checker) {
    $this->checkers[] = $checker;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSnapshot() {
    $checks = [];
    foreach ($this->checkers as $checker) {
      $checks = array_merge($checks, $checker->getChecks());
    }

    $snapshot = [
      'timestamp' => date(\DateTime::ISO8601),
      'client_id' => $this->config->get('client_id'),
      'site_id' => $this->config->get('site_id'),
      'env' => $this->config->get('env'),
      'checks' => $checks,
    ];
    return $snapshot;
  }

}
