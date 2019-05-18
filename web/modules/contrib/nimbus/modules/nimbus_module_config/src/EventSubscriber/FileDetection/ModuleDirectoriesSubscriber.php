<?php

namespace Drupal\nimbus_module_config\EventSubscriber\FileDetection;

use Drupal\Core\Config\InstallStorage;
use Drupal\nimbus\config\ConfigPath;
use Drupal\nimbus\Events\ConfigDetectionPathEvent;
use Drupal\nimbus\NimbusEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ModuleDirectoriesSubscriber.
 *
 * @package Drupal\nimbus_module_config\EventSubscriber\FileDetection
 */
class ModuleDirectoriesSubscriber implements EventSubscriberInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ModuleDirectoriesSubscriber constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Create for each module a configPath.
   *
   * @param \Drupal\nimbus\Events\ConfigDetectionPathEvent $event
   *   The event object.
   */
  public function onPreCreateFileConfigManager(ConfigDetectionPathEvent $event) {
    $file_storages = [];

    $modules = $this->moduleHandler->getModuleList();

    // Get an array of modules to ignore.
    global $_nimbus_config_ignore_modules;

    // Make sure we're dealing with an empty array if no modules are configured
    // to be ignored.
    if ((!(isset($_nimbus_config_ignore_modules))) && (!(is_array($_nimbus_config_ignore_modules)))) {
      $_nimbus_config_ignore_modules = [];
    }

    foreach ($modules as $module) {
      // Exclude profile as well as ignored modules and
      // add other modules as configuration sources.
      if (($module->getType() != 'profile') && (!(in_array($module->getName(), $_nimbus_config_ignore_modules)))) {
        $extension_path = $this->drupalGetPath($module->getType(), $module->getName()) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
        $file_storages[] = new ConfigPath($extension_path);
      }
    }

    $event->addFileStorage($file_storages);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NimbusEvents::ADD_PATH][] = ['onPreCreateFileConfigManager', 20];
    return $events;
  }

  /**
   * Wrapper for drupal_get_path().
   *
   * @param string $type
   *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
   *   'theme_engine'.
   * @param string $name
   *   The name of the item for which the path is requested. Ignored for
   *   $type 'core'.
   *
   * @return string
   *   The path to the requested item or an empty string if the item is not
   *   found.
   */
  protected function drupalGetPath($type, $name) {
    return drupal_get_path($type, $name);
  }

}
