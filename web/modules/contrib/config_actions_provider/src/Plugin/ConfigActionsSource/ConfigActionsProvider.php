<?php

namespace Drupal\config_actions_provider\Plugin\ConfigActionsSource;

use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\config_actions\ConfigActionsSourceBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for config from an injected configuration provider storage.
 *
 * @ConfigActionsSource(
 *   id = \Drupal\config_actions_provider\Plugin\ConfigActionsSource\ConfigActionsProvider::ID,
 *   description = @Translation("Use the Configuration Provider storage."),
 *   weight = 100,
 * )
 */
class ConfigActionsProvider extends ConfigActionsSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration action source ID.
   */
  const ID = 'provider';

  /**
   * The provider configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $providerStorage;

  /**
   * Constructs a new ConfigActionsProvider.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigActionsServiceInterface $config_actions_service
   *   The config actions service.
   * @param \Drupal\Core\Config\StorageInterface $provider_storage
   *   The provider configuration storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigActionsServiceInterface $config_actions_service, StorageInterface $provider_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_actions_service);
    $this->providerStorage = $provider_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config_actions'),
      $container->get('config_provider.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function detect($source) {
    // Check for a valid configuration ID.
    return (is_string($source) && (strpos($source, '.') > 0));
  }

  /**
   * {@inheritdoc}
   */
  public function doLoad() {
    $this->setMerge(FALSE);
    return $this->providerStorage->read($this->sourceId);
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($data) {
    return $this->providerStorage->write($this->sourceId, $data);
  }

}
