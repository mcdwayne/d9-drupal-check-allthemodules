<?php

namespace Drupal\stencil\Asset;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\stencil\StencilComponent;
use Drupal\stencil\StencilRegistry;

/**
 * Discovers available Stencil registries and components in the filesystem.
 */
class StencilDiscovery {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The file cache object.
   *
   * @var \Drupal\Component\FileCache\FileCacheInterface
   */
  protected $fileCache;

  /**
   * Previously discovered registry files keyed by origin directory.
   *
   * @var array
   */
  protected static $files = [];

  /**
   * Constructs a StencilDiscoveryCollector object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @apram \Drupal\Core\Extension\ThemeHandlerInterface
   *   The theme handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->fileCache = FileCacheFactory::get('stencil_discovery');
  }

  /**
   * Gets all Stencil components present on the site.
   *
   * @return \Drupal\stencil\StencilComponent[]
   *   An array of Stencil components, keyed by tag name.
   */
  public function getComponents() {
    $components = [];
    $registries = $this->getRegistries();
    foreach ($registries as $registry) {
      if ($manifest = $this->getManifest($registry->root)) {
        foreach ($manifest['components'] as $raw_component) {
          $props = [];
          foreach ($raw_component['props'] as $prop) {
            $props[] = $prop['name'];
          }
          $tag = strtolower($raw_component['tag']);
          $components[$tag] = new StencilComponent($registry->namespace, $tag, $props);
        }
      }
    }
    return $components;
  }

  /**
   * Gets all Stencil registries present on the site.
   *
   * @return \Drupal\stencil\StencilRegistry[]
   *   An array of Stencil registries, keyed by namespace.
   */
  public function getRegistries() {
    $directories = [];
    $directories += $this->moduleHandler->getModuleDirectories();
    $directories += $this->themeHandler->getThemeDirectories();
    $files = [];
    foreach ($directories as $directory) {
      if (!isset(static::$files[$directory])) {
        static::$files[$directory] = $this->scanDirectory($directory);
      }
      if (isset(static::$files[$directory])) {
        $files += static::$files[$directory];
      }
    }
    $registries = [];
    foreach ($files as $file) {
      $directory = dirname($file->uri);
      $namespace = explode('.', $file->filename)[0];
      if ($registry = $this->getRegistry($namespace, $directory)) {
        $registries[$namespace] = $registry;
      }
    }
    return $registries;
  }

  /**
   * Gets the manifest for a given Stencil component directory.
   *
   * @param string $directory
   *   A Stencil component directory.
   * @return bool|array
   *   The manifest array.
   */
  protected function getManifest($directory) {
    $manifest_file = $directory . '/collection/collection-manifest.json';
    if ($manifest = $this->fileCache->get($manifest_file)) {
      return $manifest;
    }
    if (file_exists($manifest_file)) {
      if ($manifest = json_decode(file_get_contents($manifest_file), TRUE)) {
        $this->fileCache->set($manifest_file, $manifest);
        return $manifest;
      }
    }
    return FALSE;
  }

  /**
   * Gets the registry for a given Stencil component directory.
   *
   * @param string $namespace
   *   The namespace of the registry.
   * @param string $directory
   *   A Stencil component directory.
   * @return bool|\Drupal\stencil\StencilRegistry
   *   The stencil registry, or FALSE if errors were encountered.
   */
  protected function getRegistry($namespace, $directory) {
    $registry_file = "$directory/$namespace.registry.json";
    if ($registry = $this->fileCache->get($registry_file)) {
      return $registry;
    }
    if (file_exists($registry_file)) {
      if ($json = json_decode(file_get_contents($registry_file), TRUE)) {
        $registry = new StencilRegistry($directory, $json['namespace'], $json['components'], $json['loader'], $json['core'], $json['corePolyfilled']);
        $this->fileCache->set($registry_file, $registry);
        return $registry;
      }
    }
    return FALSE;
  }

  /**
   * Gets all Stencil registry files in a given directory.
   *
   * @param string $directory
   *   An extension's directory (i.e. /var/www/html/modules/foo).
   * @return array
   *   An associative array (keyed by registry file) of objects with 'uri',
   *   'filename', and 'name' properties corresponding to the matched files.
   */
  protected function scanDirectory($directory) {
    $files = [];
    $directory .= '/stencil';
    if (file_exists($directory)) {
      // Some stencil components may have committed their www/build directory,
      // which would lead to duplicate component definitions. Ignore 'em!
      $ignore_directories = Settings::get('file_scan_ignore_directories', []);
      $ignore_directories[] = 'build';
      array_walk($ignore_directories, function(&$value) {
        $value = preg_quote($value, '/');
      });
      $nomask = '/^' . implode('|', $ignore_directories) . '$/';

      $files = file_scan_directory($directory, '/^[a-z0-9._-]+\.registry\.json$/', [
        'nomask' => $nomask,
      ]);
    }
    return $files;
  }

}
