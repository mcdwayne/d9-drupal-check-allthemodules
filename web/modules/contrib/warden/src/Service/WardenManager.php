<?php

namespace Drupal\warden\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use WardenApi\Api;
use Drupal\Core\Extension\Extension;

/**
 * Default controller for the warden module.
 */
class WardenManager extends Api {

  /**
   * @var InfoParser
   */
  protected $infoParser;

  /**
   * @var string
   */
  protected $siteName;

  /**
   * @var string
   */
  protected $baseUrl;

  /**
   * @var string
   */
  protected $coreVersion;

  /**
   * @var Extension[]
   */
  protected $themes;

  /**
   * @var Extension[]
   */
  protected $modules;

  /**
   * @var Extension[]
   */
  protected $libraries;

  /**
   * @var bool
   */
  protected $includeContrib;

  /**
   * @var bool
   */
  protected $includeCustom;

  /**
   * @var bool
   */
  protected $includeLibrary;

  /**
   * @var array
   */
  protected $customLibraries;

  /**
   * @var string
   */
  protected $customModuleRegex;

  /**
   * @var string
   */
  protected $contribModuleRegex;

  /**
   * @var string
   */
  protected $localToken;

  /**
   * @var int
   */
  protected $time;

  /**
   * @param ConfigFactory $configFactory
   */
  public function __construct(ConfigFactory $configFactory) {
    $warden_settings = $configFactory->get('warden.settings');

    $this->siteName = $configFactory->get('system.site')->get('name');
    $this->contribModuleRegex = $warden_settings->get('warden_preg_match_contrib');
    $this->customModuleRegex = $warden_settings->get('warden_preg_match_custom');
    $this->includeCustom = $warden_settings->get('warden_match_custom');
    $this->includeContrib = $warden_settings->get('warden_match_contrib');
    $this->includeLibrary = $warden_settings->get('warden_match_library');
    $this->customLibraries = $warden_settings->get('warden_list_libraries');

    $local_token = $warden_settings->get('warden_token');
    if (!empty($local_token)) {
      $this->localToken = $local_token;
    }

    $this->coreVersion = \Drupal::VERSION;

    parent::__construct(
      $warden_settings->get('warden_server_host_path'),
      $warden_settings->get('warden_http_username'),
      $warden_settings->get('warden_http_password'),
      $warden_settings->get('warden_certificate_path')
    );
  }

  /**
   * Generate all the site's data for Warden.
   *
   * @return array
   *   The site's data as an array.
   */
  public function generateSiteData() {
    $result = [
      'core' => [
        'drupal' => [
          'version' => $this->getCoreVersion(),
        ],
      ],
      'contrib' => [],
      'custom' => [],
      'library' => [],
      'url' => $this->getBaseUrl(),
      'site_name' => $this->getSiteName(),
      'key' => $this->getLocalToken(),
      'time' => $this->getTime(),
    ];

    // Indicate if we are not including custom modules.
    if (!$this->includeCustomModules()) {
      $result['custom'] = 'disabled';
    }

    // Indicate if we are not including contrib modules.
    if (!$this->includeContribModules()) {
      $result['contrib'] = 'disabled';
    }

    // Indicate if we are not including third party libraries.
    if (!$this->includeLibrary()) {
      $result['library'] = 'disabled';
    }

    // Include all contrib themes.
    if ($this->includeContribModules()) {
      foreach ($this->getThemes() as $theme) {
        if (isset($theme->info['package']) && $theme->info['package'] == 'Core') {
          continue;
        }

        if (isset($theme->info['version'])) {
          $result['contrib'][$theme->name] = [
            'version' => $theme->info['version'],
          ];
        }
      }
    }

    // Include all modules.
    foreach ($this->getModules() as $module => $module_info) {
      $filename = $module_info->getPathname();

      // Match for custom modules.
      if ($this->includeCustomModules() && preg_match($this->getCustomRegex(), $filename)) {
        $result['custom'] = array_merge($result['custom'], $this->getModuleDetails($module, $filename));
      }

      // Match for contrib modules.
      if ($this->includeContribModules() && preg_match($this->getContribRegex(), $filename)) {
        $result['contrib'] = array_merge($result['contrib'], $this->getModuleDetails($module, $filename));
      }
    }

    // Include all third party libraries.
    if ($this->includeLibrary()) {
      if (isset($this->customLibraries)) {
        $result['library'] = $this->customLibraries;
      }
      else {
        foreach ($this->getLibraries() as $library_uri => $library_info) {
          $result['library'] = array_merge($result['library'], $this->getLibraryDetails($library_uri));
        }
      }
    }

    return $result;
  }

  /**
   * Update Warden with latest site data.
   *
   * @throws \Exception
   *   If any problems occur.
   */
  public function updateWarden() {
    $data = $this->generateSiteData();
    $this->postSiteData($data);
  }

  /**
   * Get the local token and generate it if it is not set.
   *
   * @return string
   *   The local token
   */
  public function getLocalToken() {
    if (!isset($this->localToken)) {
      $this->localToken = $this->generateNewLocalToken();
    }

    return $this->localToken;
  }

  /**
   * Generate and save and new token.
   *
   * @return string
   */
  protected function generateNewLocalToken() {
    $local_token = hash('sha256', mt_rand());
    \Drupal::configFactory()
      ->getEditable('warden.settings')
      ->set('warden_token', $local_token)
      ->save();
    return $local_token;
  }

  /**
   * Gets an array of the module information that is used on the site.
   *
   * @param string $module
   *   The name of the module
   * @param string $filename
   *   A full path to an info yml file.
   *
   * @return array
   *   key is a project name or module name, value is an array of its details.
   */
  protected function getModuleDetails($module, $filename) {
    $details = $this->getInfoParser()->parse($filename);
    if (!empty($details['project'])) {
      return [
        $details['project'] => [
          'version' => isset($details['version']) ? $details['version'] : '',
        ]
      ];
    }
    else {
      return [
        $module => [
          'version' => isset($details['version']) ? $details['version'] : '',
        ]
      ];
    }
  }

  /**
   * Gets an array of the third party library information on the site.
   *
   * @param string $filename
   *   A full path to the file that contains the version information.
   *
   * @return array
   *   key is a third party library name, value is an array of its details.
   */
  protected function getLibraryDetails($filename) {
    $details = Json::decode(file_get_contents($filename));
    return [
      $details['name'] => isset($details['version']) ? $details['version'] : '',
    ];
  }

  /**
   * @return string
   */
  protected function getSiteName() {
    return $this->siteName;
  }

  /**
   * @return string
   */
  protected function getCoreVersion() {
    return $this->coreVersion;
  }

  /**
   * @return string
   *   The regex telling us where a contrib module is located.
   */
  protected function getContribRegex() {
    return $this->contribModuleRegex;
  }

  /**
   * @return string
   *   The regex telling us where a custom module is located.
   */
  protected function getCustomRegex() {
    return $this->customModuleRegex;
  }

  /**
   * @return bool
   */
  protected function includeCustomModules() {
    return $this->includeCustom;
  }

  /**
   * @return bool
   */
  protected function includeContribModules() {
    return $this->includeContrib;
  }

  /**
   * @return bool
   */
  protected function includeLibrary() {
    return $this->includeLibrary;
  }

  /**
   * @param Extension[] $themes
   * @return $this
   */
  public function setThemes(array $themes) {
    $this->themes = $themes;
    return $this;
  }

  /**
   * @return \Drupal\Core\Extension\Extension[]
   */
  protected function getThemes() {
    if (!isset($this->themes)) {
      $this->setThemes(system_list('theme'));
    }

    return $this->themes;
  }

  /**
   * @param Extension[] $modules
   * @return $this
   */
  public function setModules(array $modules) {
    $this->modules = $modules;
    return $this;
  }

  /**
   * @return Extension[]
   */
  protected function getModules() {
    if (!isset($this->modules)) {
      $listing = new ExtensionDiscovery(\Drupal::root());
      $this->setModules($listing->scan('module'));
    }

    return $this->modules;
  }

  /**
   * @param \stdClass[] $libraries
   * @return $this
   */
  public function setLibraries(array $libraries) {
    $this->libraries = $libraries;
    return $this;
  }

  /**
   * @return \stdClass[]
   */
  protected function getLibraries() {
    if (!isset($this->libraries)) {
      // Scan the libraries directory for libraries which have been added.
      $libraryDir = \Drupal::root() . '/libraries';
      if (file_exists($libraryDir)) {
        // Scan for package.json files for libraries that have this.
        $listing = file_scan_directory($libraryDir, '/^package.json/');
        $this->setLibraries($listing);
      }
    }

    return $this->libraries;
  }

  /**
   * @return \Drupal\Core\Extension\InfoParser
   */
  protected function getInfoParser() {
    if (empty($this->infoParser)) {
      $this->setInfoParser(new InfoParser());
    }

    return $this->infoParser;
  }

  /**
   * @param \Drupal\Core\Extension\InfoParser $infoParser
   * @return $this
   */
  public function setInfoParser(InfoParser $infoParser) {
    $this->infoParser = $infoParser;
    return $this;
  }

  /**
   * @return string
   */
  protected function getBaseUrl() {
    if (!isset($this->baseUrl)) {
      global $base_url;
      $this->setBaseUrl($base_url);
    }

    return $this->baseUrl;
  }

  /**
   * @param string $baseUrl
   * @return $this
   */
  public function setBaseUrl($baseUrl) {
    $this->baseUrl = $baseUrl;
    return $this;
  }

  /**
   * @return int
   */
  protected function getTime() {
    if (!isset($this->time)) {
      $this->setTime(time());
    }

    return $this->time;
  }

  /**
   * @param int $time
   * @return $this
   */
  public function setTime($time) {
    $this->time = $time;
    return $this;
  }

}
