<?php

namespace Drupal\social_hub\Utils;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Resolve libraries installed/defined in modules.
 */
class ModuleLibrariesResolver extends BaseExtensionResolver {

  /**
   * The moduleHandler property.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs ModuleLibrariesResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, ModuleHandlerInterface $module_handler) {
    parent::__construct($library_discovery);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions() {
    return $this->moduleHandler->getModuleList();
  }

}
