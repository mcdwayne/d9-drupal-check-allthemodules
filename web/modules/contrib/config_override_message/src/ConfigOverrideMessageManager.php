<?php

namespace Drupal\config_override_message;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Config override message manager.
 */
class ConfigOverrideMessageManager implements ConfigOverrideMessageManagerInterface {

  /**
   * Constants for the override directory.
   */
  const CONFIG_OVERRIDE_DIRECTORY = 'override';

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructs a ConfigOverrideMessageSubscriber object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   */
  public function __construct($root, ModuleHandlerInterface $moduleHandler, CacheBackendInterface $cacheBackend) {
    $this->root = $root;
    $this->moduleHandler = $moduleHandler;
    $this->cacheBackend = $cacheBackend;
  }

  /****************************************************************************/
  // Overrides.
  /****************************************************************************/

  /**
   * Get config overrides.
   *
   * @return array
   *   An associative array of messages keyed by path.
   */
  public function getOverrides() {
    $overrides = [
      'site' => [],
      'modules' => [],
    ];

    // Get site configuration override messages.
    $files = file_scan_directory($this->getSiteConfigOverrideFolder(), '/.yml$/');
    foreach ($files as $file) {
      $data = Yaml::decode(file_get_contents($file->uri));
      $overrides['site'][$file->name] = $data;
    }

    // Get module config override messages.
    $modules = $this->moduleHandler->getModuleList();
    foreach ($modules as $module) {
      $folder = $this->root . '/' . $module->getPath() . '/config/override';
      if (file_exists($folder)) {
        $file_storage = new FileStorage($folder);
        $overrides['modules'][$module->getName()] = $file_storage->readMultiple($file_storage->listAll());
        ksort($overrides['modules'][$module->getName()]);
      }
    }

    ksort($overrides['site']);
    ksort($overrides['modules']);

    return $overrides;
  }

  /****************************************************************************/
  // Messages.
  /****************************************************************************/

  /**
   * Get config override messages.
   *
   * @return array
   *   An associative array of messages keyed by path.
   */
  public function getMessages() {
    if ($cache = $this->cacheBackend->get('config_override_messages')) {
      // return $cache->data;
    }

    $messages = [];

    // Get site configuration override messages.
    $files = file_scan_directory($this->getSiteConfigOverrideFolder(), '/.yml$/');
    foreach ($files as $file) {
      $data = Yaml::decode(file_get_contents($file->uri));
      $this->appendMessages($messages, $data);
    }

    // Get module config override messages.
    $modules = $this->moduleHandler->getModuleList();
    foreach ($modules as $module) {
      $folder = $this->root . '/' . $module->getPath() . '/config/override';
      if (file_exists($folder)) {
        $file_storage = new FileStorage($folder);
        $configs = $file_storage->readMultiple($file_storage->listAll());
        foreach ($configs as $data) {
          $this->appendMessages($messages, $data);
        }
      }
    }

    $this->cacheBackend->set('config_override_messages', $messages);
    return $messages;
  }

  /**
   * Append overridden config messages to messages.
   *
   * @param array $messages
   *   Associative array of overridden config messages.
   * @param array $data
   *   Associative array containing configuration data.
   */
  protected function appendMessages(array &$messages, array $data) {
    if (!isset($data['_config_override_message']) || !isset($data['_config_override_paths'])) {
      return;
    }

    $message = $data['_config_override_message'];
    $paths = $data['_config_override_paths'];
    foreach ($paths as $path) {
      if (!isset($messages[$path])) {
        $messages[$path] = [];
      }
      $messages[$path][] = $message;
    }
  }

  /****************************************************************************/
  // Site configuration override methods.
  // @see \Drupal\config_override\SiteConfigOverrides::getSiteConfigOverrideFolder
  /****************************************************************************/

  /**
   * Returns the site config overrides directory or NULL if it was not defined.
   *
   * @return string|null
   *   The site config overrides directory or NULL if it was not defined.
   */
  protected function getSiteConfigOverrideFolder() {
    try {
      $config_override_directory = config_get_config_directory(static::CONFIG_OVERRIDE_DIRECTORY);
      if (file_exists($config_override_directory)) {
        return $config_override_directory;
      }
      elseif (file_exists($this->root . '/' . $config_override_directory)) {
        return $this->root . '/' . $config_override_directory;
      }
      else {
        throw new \Exception("The configuration directory '$config_override_directory' does not exist");
      }
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
