<?php

namespace Drupal\config_actions\Plugin\ConfigActionsSource;

use Drupal\config_actions\ConfigActionsSourceBase;
use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\File\FileSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for config source from files.
 *
 * @ConfigActionsSource(
 *   id = "template",
 *   description = @Translation("Use a config/template file."),
 *   weight = "-1",
 * )
 */
class ConfigActionsTemplate extends ConfigActionsSourceBase {

  /**
   * @var string
   *   The name of the default sub-directory containing config templates.
   */
  const CONFIG_TEMPLATE_DIRECTORY = 'config/templates';

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $templateStorage;

  /**
   * Constructs a new ConfigActionsSource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigActionsServiceInterface $config_action_service
   *   The ConfigActionsService from the container.
   * @param FileSystem $file_system
   *   The FileSystem from the container.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigActionsServiceInterface $config_action_service, StorageInterface $config_storage) {
    $this->templateStorage = new ExtensionInstallStorage($config_storage, ConfigActionsFile::CONFIG_TEMPLATE_DIRECTORY);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_action_service);
  }

  /**
   * Create a plugin instance from the container
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var ConfigActionsServiceInterface $config_action_service */
    $config_action_service = $container->get('config_actions');
    /** @var StorageInterface $config_storage */
    $config_storage = $container->get('config.storage');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config_action_service,
      $config_storage
    );
  }

  /**
   * {@inheritdoc}
   */
  public function detect($source) {
    // No auto-detection for templates
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function doLoad() {
    $this->setMerge(TRUE);
    return $this->templateStorage->read($this->sourceId);
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($data) {
    // Cannot save templates, use a File instead.
    return FALSE;
  }

}
