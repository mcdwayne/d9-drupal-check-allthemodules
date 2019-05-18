<?php

namespace Drupal\jsonrpc_core\Plugin\jsonrpc\Method;

use Drupal\Core\State\StateInterface;
use Drupal\jsonrpc\MethodInterface;
use Drupal\jsonrpc\Object\ParameterBag;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RPC method to enable or disable maintenance mode.
 *
 * @JsonRpcMethod(
 *   id = "maintenance_mode.isEnabled",
 *   usage = @Translation("Enables or disables the maintenance mode."),
 *   access = {"administer site configuration"},
 *   params = {
 *     "enabled" = @JsonRpcParameterDefinition(schema={"type"="boolean"}),
 *   }
 * ),
 */
class MaintenanceModeEnabled extends JsonRpcMethodBase {

  const ENABLED = 'enabled';
  const DISABLED = 'disabled';

  /**
   * The state API service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * MaintenanceModeEnabled constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param \Drupal\jsonrpc\MethodInterface $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state API service.
   */
  public function __construct(array $configuration, $plugin_id, MethodInterface $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * Create an instance of this plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The DI container.
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\jsonrpc_core\Plugin\jsonrpc\Method\MaintenanceModeEnabled
   *   An instance of the MaintenanceModeEnabled plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('state'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ParameterBag $parameters) {
    $enabled = $parameters->get('enabled');

    $this->state->set('system.maintenance_mode', $enabled);
    return $this->state->get('system.maintenance_mode')
      ? static::ENABLED
      : static::DISABLED;
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema() {
    return ['type' => 'string'];
  }

}
