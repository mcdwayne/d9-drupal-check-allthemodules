<?php

namespace Drupal\acquia_contenthub\Plugin\FileSchemeHandler;

use Drupal\acquia_contenthub\Annotation\FileSchemeHandler;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\file\FileInterface;

/**
 * Class FileSchemeHandlerManager.
 *
 * @package Drupal\acquia_contenthub\Plugin\FileSchemeHandler
 */
class FileSchemeHandlerManager extends DefaultPluginManager implements FileSchemeHandlerManagerInterface {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new \Drupal\Core\Block\BlockManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system) {
    parent::__construct('Plugin/FileSchemeHandler', $namespaces, $module_handler, FileSchemeHandlerInterface::class, FileSchemeHandler::class);

    $this->alterInfo('file_scheme_handler');
    $this->setCacheBackend($cache_backend, 'file_scheme_handler_plugins');
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function getHandlerForFile(FileInterface $file) {
    $uri = $file->getFileUri();
    $scheme = $this->fileSystem->uriScheme($uri);
    if (!$scheme) {
      throw new \Exception(sprintf('Failed to load file scheme for %s URI.', $uri));
    }
    return $this->createInstance($scheme);
  }

}
