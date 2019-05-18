<?php

namespace Drupal\domain_libraries_attach;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Routing\AdminContext;

/**
 * Class DomainLibrariesManager.
 *
 * @package Drupal\domain_libraries_attach
 */
class DomainLibrariesManager {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Domain Negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Theme Handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Library Discovery.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Admin Context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Machine name of default theme.
   *
   * @var string
   */
  public $defaultThemeId = NULL;

  /**
   * Human readable name of default theme.
   *
   * @var string
   */
  public $defaultThemeName = NULL;

  /**
   * Indicate type of current route.
   *
   * @var bool
   */
  public $isAdminRoute = FALSE;

  /**
   * DomainLibrariesManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   Domain Negotiator.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   Theme Handler.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library Discovery.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   Admin Context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainNegotiatorInterface $domain_negotiator, ThemeHandlerInterface $theme_handler, LibraryDiscoveryInterface $library_discovery, AdminContext $admin_context) {
    $this->configFactory = $config_factory;
    $this->domainNegotiator = $domain_negotiator;
    $this->themeHandler = $theme_handler;
    $this->libraryDiscovery = $library_discovery;
    $this->adminContext = $admin_context;
    $this->isAdminRoute = $this->adminContext->isAdminRoute();
    $this->defaultThemeId = $this->themeHandler->getDefault();
    $this->defaultThemeName = $this->themeHandler->getName($this->defaultThemeId);
  }

  /**
   * Returns the list of only extra libraries.
   *
   * @param bool $themePrefix
   *   Prepend theme name to library name.
   *
   * @return array|null
   *   Array of libraries names or NULL.
   */
  public function getLibraries($themePrefix = TRUE) {
    if (is_null($this->defaultThemeId)) {
      return NULL;
    }

    $theme = $this->themeHandler->getTheme($this->defaultThemeId);

    /*
     * If there is no libraries defined in <theme_name>.info.yml file
     * return all libraries defined in <theme_name>.libraries.yml file.
     */
    if (!isset($theme->libraries)) {
      return $this->getAllLibraries($themePrefix);
    }

    $definedLibraries = $theme->libraries;

    /*
     * Libraries names in $definedLibraries array have theme prefix by default.
     * Remove theme prefix if flag is FALSE.
     */
    if (!$themePrefix) {
      foreach ($definedLibraries as $index => $libraryName) {
        $explodedLibName = explode('/', $libraryName);
        $definedLibraries[$index] = array_pop($explodedLibName);
      }
    }

    $allLibraries = $this->getAllLibraries($themePrefix);
    /*
     * Exclude all libraries defined in <theme_name>.info.yml files.
     */
    $extraLibraries = array_diff($allLibraries, $definedLibraries);

    return $extraLibraries;
  }

  /**
   * Returns the list of all libraries defined in theme.
   *
   * @param bool $themePrefix
   *   Prepend theme name to library name.
   *
   * @return array|null
   *   Array of libraries names or NULL.
   */
  public function getAllLibraries($themePrefix = TRUE) {
    if (is_null($this->defaultThemeId)) {
      return NULL;
    }

    $libraries = array_keys($this->libraryDiscovery->getLibrariesByExtension($this->defaultThemeId));

    if ($themePrefix) {
      foreach ($libraries as $index => $libraryName) {
        $libraries[$index] = $this->defaultThemeId . '/' . $libraryName;
      }
    }

    return $libraries;
  }

  /**
   * Returns the list of libraries formatted for a form options list.
   *
   * @return array
   *   Associative array $id => $name.
   */
  public function getOptionsList() {
    $libraries = $this->getLibraries();

    if ($libraries) {
      return array_combine($libraries, $this->getLibraries(FALSE));
    }

    return [];
  }

  /**
   * Returns list of libraries assigned to current domain.
   *
   * @return array|null
   *   Array of libraries names.
   */
  public function getLibrariesForCurrentDomain() {
    if ($activeDomain = $this->domainNegotiator->getActiveDomain()) {
      $activeDomainId = $activeDomain->id();
      /*
       * @todo Refactor getting config object to follow best practice:
       * https://www.drupal.org/docs/8/api/configuration-api/simple-configuration-api#config-writing
       */
      $libraries = $this->configFactory->get('domain_libraries_attach.settings')->get($activeDomainId);
      return $libraries;
    }

    return NULL;
  }

}
