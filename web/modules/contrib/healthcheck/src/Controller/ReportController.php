<?php

namespace Drupal\healthcheck\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\healthcheck\Form\RunReportForm;
use Drupal\healthcheck\HealthcheckServiceInterface;
use Drupal\healthcheck\Plugin\HealthcheckPluginManager;
use Drupal\healthcheck\Report\Report;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ReportController.
 */
class ReportController extends ControllerBase {

  /**
   * The healthcheck service
   *
   * @var HealthcheckServiceInterface
   */
  protected $healthcheck_service;

  /**
   * Constructs a new ReportController object.
   */
  public function __construct(HealthcheckServiceInterface $healthcheck_service) {
    $this->healthcheck_service = $healthcheck_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('healthcheck')
    );
  }

  /**
   * Run report.
   *
   * @return array
   *   The render array for the report.
   */
  public function runReport() {
    $report = $this->healthcheck_service->runReport();

    // Create the initial render array.
    $out = [
      '#theme' => 'healthcheck_report',
      '#report' => $report,
    ];

    return $out;
  }

}
