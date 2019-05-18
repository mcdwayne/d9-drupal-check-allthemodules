<?php

namespace Drupal\libraries_provider;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides methods for querying library information.
 */
class LibrariesProviderManager {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LibrariesProviderManager instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeHandlerInterface $themeHandler
   *   The theme manager.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The library discovery service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    ThemeHandlerInterface $themeHandler,
    LibraryDiscoveryInterface $libraryDiscovery,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
    $this->libraryDiscovery = $libraryDiscovery;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gather all the libraries handled by this module.
   */
  public function getManagedLibraries() {
    $libraries = [];
    $extensions = array_merge(
      array_keys($this->moduleHandler->getModuleList()),
      array_keys($this->themeHandler->listInfo())
    );
    foreach ($extensions as $extension) {
      foreach ($this->libraryDiscovery->getLibrariesByExtension($extension) as $name => $library) {
        if (!empty($library['libraries_provider'])) {
          $library['libraries_provider']['extension'] = $extension;
          $libraries[$extension . '__' . $name] = $library;
        }
      }
    }
    return $libraries;
  }

  /**
   * Get requirements left to fulfill.
   *
   * Before a library can set custom options alll the requirements
   * need to be fulfiled.
   */
  public function getCustomOptionsRequirements(array $customOptions) {
    $requirements = [];
    $libraryEntities = $this->entityTypeManager->getStorage('library')->loadMultiple();
    foreach ($customOptions['requirements']['libraries'] ?? [] as $requirementLibraryId => $requirementType) {
      if (!(
        $requirementType === 'local' &&
        isset($libraryEntities[$requirementLibraryId]) &&
        $libraryEntities[$requirementLibraryId]->get('source') === 'local'
      )) {
        list($extension, $libraryName) = explode('__', $requirementLibraryId);
        $requirements[] = $this->t('The library "@libraryName" provided by the extension "@extension" needs to be in the local filesystem and use the local source', [
          '@libraryName' => $libraryName,
          '@extension' => $extension,
        ]);
      }
    }
    foreach ($customOptions['requirements']['extensions'] ?? [] as $phpExtension => $phpExtensionUrl) {
      if (!extension_loaded($phpExtension)) {
        $requirements[] = $this->t('The PHP extension "<a href="@phpExtensionUrl">@phpExtension</a>" is required to customize the options', [
          '@phpExtensionUrl' => $phpExtensionUrl,
          '@phpExtension' => $phpExtension,
        ]);
      }
    }
    return $requirements;
  }

}
