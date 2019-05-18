<?php

namespace Drupal\prometheus_exporter\Plugin\MetricsCollector;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\prometheus_exporter\Plugin\BaseMetricsCollector;
use Drupal\update\UpdateManagerInterface;
use PNX\Prometheus\Gauge;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects metrics for module status.
 *
 * @MetricsCollector(
 *   id = "update_status",
 *   title = @Translation("Update status"),
 *   description = @Translation("Provides metrics for module update status.")
 * )
 */
class UpdateStatusCollector extends BaseMetricsCollector implements ContainerFactoryPluginInterface {

  /**
   * The update manager.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * The module hander.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * UpdateStatusCollector constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\update\UpdateManagerInterface $updateManager
   *   The update manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UpdateManagerInterface $updateManager, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->updateManager = $updateManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('update.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collectMetrics() {

    $projects = $this->updateManager->getProjects();

    foreach ($projects as $name => $project) {
      $project_type = $project['project_type'];
      $version = $project['info']['version'];
      switch ($project_type) {
        case 'core':
          if (!isset($coreMetric)) {
            $coreMetric = new Gauge($this->getNamespace(), 'core_version', 'Drupal core version');
          }
          $coreMetric->set(1, ['version' => $version]);
          break;

        case 'module':
          if (!isset($moduleMetric)) {
            $moduleMetric = new Gauge($this->getNamespace(), 'module_version', 'Drupal module version');
          }
          $moduleMetric->set(1, ['name' => $name, 'version' => $version]);
          break;

        case 'theme':
          if (!isset($themeMetric)) {
            $themeMetric = new Gauge($this->getNamespace(), 'theme_version', 'Drupal theme version');
          }
          $themeMetric->set(1, ['name' => $name, 'version' => $version]);
          break;
      }
    }

    $metrics = [];
    if (isset($coreMetric)) {
      $metrics[] = $coreMetric;
    }
    if (isset($moduleMetric)) {
      $metrics[] = $moduleMetric;
    }
    if (isset($themeMetric)) {
      $metrics[] = $themeMetric;
    }

    return $metrics;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return $this->moduleHandler->moduleExists('update');
  }

}
