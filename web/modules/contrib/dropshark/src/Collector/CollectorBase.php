<?php

namespace Drupal\dropshark\Collector;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CollectorBase.
 */
abstract class CollectorBase extends PluginBase implements CollectorInterface {

  const API_VERSION = '0.0.1';

  use ContainerAwareTrait;

  /**
   * CollectorBase constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   */
  public function __construct(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $container,
      $configuration,
      $pluginId,
      $pluginDefinition
    );
  }

  /**
   * A default value to be used for a collector's result.
   *
   * This contains the properties needed to report a valid result. Collectors
   * will add and overwrite properties as needed.
   *
   * Note: 'ds_timestamp' and 'ds_fingerprint' properties gets added when the
   * collected data is queued.
   *
   * @param string $type
   *   The type indicator of the data being collected.
   *
   * @return array
   *   Array of collected data.
   */
  protected function defaultResult($type = NULL) {
    $result = [];

    if (!$type) {
      $type = $this->getPluginId();
    }

    $result['site_id'] = $this->getState()->get('dropshark.site_id');
    $result['type'] = $type;
    $result['server'] = $this->getServer();
    $result['ds_collector_id'] = "{$result['type']}|{$result['server']}";
    $result['code'] = 'unknown_error';
    $result['fingerprint'] = $this->getFingerPrint()->getFingerprint();
    $result['ds_api_version'] = static::API_VERSION;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize() {
    // No op.
  }

  /**
   * The server where the data is being collected.
   *
   * Metrics specific to a server should specify which server they're collected
   * from.
   *
   * @return string
   *   The server identifier.
   */
  protected function getServer() {
    // TODO: make this dynamic, configurable.
    return 'default';
  }

  /**
   * Gets the DropShark module configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   DropShark module configuration.
   */
  protected function getConfig() {
    return $this->container->get('config.factory')->get('dropshark.settings');
  }

  /**
   * Gets the fingerprint service.
   *
   * @return \Drupal\dropshark\Fingerprint\FingerprintInterface
   *   The fingerprint service.
   */
  protected function getFingerPrint() {
    return $this->container->get('dropshark.fingerprint');
  }

  /**
   * Gets the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected function getModuleHandler() {
    return $this->container->get('module_handler');
  }

  /**
   * Gets the queue handler service.
   *
   * @return \Drupal\dropshark\Queue\QueueInterface
   *   The queue handler service.
   */
  protected function getQueue() {
    return $this->container->get('dropshark.queue');
  }

  /**
   * Gets the state service.
   *
   * @return \Drupal\Core\State\StateInterface
   *   The state service.
   */
  protected function getState() {
    return $this->container->get('state');
  }

}
