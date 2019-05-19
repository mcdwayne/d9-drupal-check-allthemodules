<?php

namespace Drupal\social_hub\Utils;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Resolve libraries installed/defined by themes.
 */
class ThemeLibrariesResolver extends BaseExtensionResolver {

  /**
   * The themeHandler property.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs ThemeLibrariesResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, ThemeHandlerInterface $theme_handler) {
    parent::__construct($library_discovery);
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions() {
    return $this->themeHandler->listInfo();
  }

}
