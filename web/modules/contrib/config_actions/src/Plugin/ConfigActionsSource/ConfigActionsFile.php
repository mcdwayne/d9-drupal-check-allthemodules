<?php

namespace Drupal\config_actions\Plugin\ConfigActionsSource;

use Drupal\config_actions\ConfigActionsSourceBase;
use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\File\FileSystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for config source from files.
 *
 * @ConfigActionsSource(
 *   id = "file",
 *   description = @Translation("Use a file."),
 *   weight = "-1",
 * )
 */
class ConfigActionsFile extends ConfigActionsSourceBase {

  /**
   * @var string
   *   The name of the default sub-directory containing config templates.
   */
  const CONFIG_TEMPLATE_DIRECTORY = 'config/templates';

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigActionsServiceInterface $config_action_service, FileSystem $file_system) {
    $this->fileSystem = $file_system;
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
    /** @var FileSystem $file_system */
    $file_system = $container->get('file_system');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config_action_service,
      $file_system
    );
  }

  /**
   * {@inheritdoc}
   */
  public function detect($source) {
    $extension = '.' . FileStorage::getFileExtension();
    return is_string($source) &&
      substr($source, -strlen($extension)) === $extension;
  }

  /**
   * Return the file name and path from the source specifier
   * @param $source
   * @param string $base
   *   Optional base path
   * @return array
   *   0 - file name
   *   1 - file path
   */
  protected function filePath($source, $base = '') {
    $filepath = $this->fileSystem->dirname($source);
    $filename = $this->fileSystem->basename($source, '.' . FileStorage::getFileExtension());
    // See if Source specifies its own path or not.
    if (empty($filepath) || ($filepath == '.')) {
      // Path not specified, so use provided base or current directory.
      $base = !empty($base) ? $base : dirname(__FILE__);
      $filepath = $base . '/' . self::CONFIG_TEMPLATE_DIRECTORY;
    }
    elseif (!empty($base)) {
      // If path was specified in Source, prepend any provided base path.
      $filepath = $base . '/' . $filepath;
    }
    return [$filepath, $filename];
  }

  /**
   * {@inheritdoc}
   */
  public function doLoad() {
    $this->setMerge(TRUE);
    list($filepath, $filename) = $this->filePath($this->sourceId, $this->sourceBase);
    $template_storage = new FileStorage($filepath, StorageInterface::DEFAULT_COLLECTION);
    return $template_storage->read($filename);
  }

  /**
   * {@inheritdoc}
   */
  public function doSave($data) {
    list($filepath, $filename) = $this->filePath($this->sourceId, $this->sourceBase);
    $template_storage = new FileStorage($filepath, StorageInterface::DEFAULT_COLLECTION);
    return $template_storage->write($filename, $data);
  }

}
