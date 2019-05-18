<?php

namespace Drupal\prometheus_exporter\Plugin\MetricsCollector;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\prometheus_exporter\Plugin\BaseMetricsCollector;
use PNX\Prometheus\Gauge;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collects metrics for php info.
 *
 * @MetricsCollector(
 *   id = "phpinfo",
 *   title = @Translation("PHP Info"),
 *   description = @Translation("Provides metrics for PHP info.")
 * )
 */
class PhpInfoCollector extends BaseMetricsCollector implements ContainerFactoryPluginInterface {

  /**
   * The PHP version.
   *
   * @var \Drupal\prometheus_exporter\Plugin\MetricsCollector\PhpVersion
   */
  protected $phpVersion;

  /**
   * PhpInfoCollector constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\prometheus_exporter\Plugin\MetricsCollector\PhpVersion $phpVersion
   *   The PHP info.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PhpVersion $phpVersion) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->phpVersion = $phpVersion;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      new PhpVersion()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collectMetrics() {
    $metrics = [];
    $version = new Gauge($this->getNamespace(), 'version', 'Provides the PHP version');
    $version->set($this->phpVersion->getId(), [
      'version' => $this->phpVersion->getString(),
      'major' => $this->phpVersion->getMajor(),
      'minor' => $this->phpVersion->getMinor(),
      'patch' => $this->phpVersion->getPatch(),
    ]);
    $metrics[] = $version;
    return $metrics;
  }

}
