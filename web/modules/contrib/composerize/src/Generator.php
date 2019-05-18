<?php

namespace Drupal\composerize;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\update\UpdateManagerInterface;

class Generator {

  /**
   * The update manager service.
   *
   * @var \Drupal\update\UpdateManagerInterface
   */
  protected $updateManager;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

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
   * Composerize's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Generator constructor.
   *
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   The update manager service.
   * @param LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(UpdateManagerInterface $update_manager, LibraryDiscoveryInterface $library_discovery, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory) {
    $this->updateManager = $update_manager;
    $this->libraryDiscovery = $library_discovery;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->config = $config_factory->get('composerize.settings');
  }

  public function generate() {
    $composer = $this->config->get('default_composer') ?: [];
    $composer['require']['drupal/core'] = \Drupal::VERSION;

    $drupal_dependencies = [];
    $projects = $this->updateManager->getProjects();
    // Don't process core.
    unset($projects['drupal']);

    foreach ($projects as $extension => $project) {
      $extension = $this->getExtension($project);
      $package_name = $this->getPackageName($extension);

      // Add the extension as an explicit dependency.
      $composer['require'][$package_name] = $this->getVersion($project);
      // Find Drupal dependencies (i.e., modules or themes) of the extension.
      $drupal_dependencies += $this->getDrupalDependencies($extension);

      // Find all of the extension's libraries with JavaScript components.
      $libraries = array_filter(
        $this->libraryDiscovery->getLibrariesByExtension($extension->getName()),
        function (array $library) {
          return isset($library['js']);
        }
      );

      $add_library = function (array $info, $dir, $repository) use (&$composer) {
        $name = $info['name'];
        $composer['require']["$repository-asset/$name"] = $info['version'];
        $dir = dirname($dir);
        $composer['extra']['installer-paths'][$dir . '/{$name}'][] = "$repository-asset/$name";
      };

      foreach ($libraries as $library) {
        $js = reset($library['js']);
        $directory = explode(DIRECTORY_SEPARATOR, dirname($js['data']));

        // Traverse upwards in the directory tree until we find either a
        // bower.json or package.json, and extract the package name and version
        // number from the first one we find (bower.json will be sought first).
        while ($directory) {
          $dir = implode(DIRECTORY_SEPARATOR, $directory);

          $info = $this->readJson($dir, 'bower.json');
          if ($info && isset($info['version'])) {
            $add_library($info, $dir, 'bower');
            break;
          }

          $info = $this->readJson($dir, 'package.json');
          if ($info && isset($info['version'])) {
            $add_library($info, $dir, 'npm');
            break;
          }

          array_pop($directory);
        }
      }

      $path = $extension->getPath();
      $dir = dirname($path);
      if ($dir === '.') {
        $dir = $path;
      }
      else {
        $dir .= '/{$name}';
      }
      $composer['extra']['installer-paths'][$dir][] = $package_name;
    }

    // Don't explicitly require dependencies of dependencies except for Drupal
    // Core which we always want to pin in our root composer.json.
    unset($drupal_dependencies['drupal/core']);
    $composer['require'] = array_diff_key($composer['require'], $drupal_dependencies);

    return json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  }

  /**
   * Generates a semantic version for a Drupal project.
   *
   * @param array $project
   *   The project info, as returned by UpdateManager::getProjects().
   *
   * @return string
   */
  protected function getVersion(array $project) {
    // Strip the 8.x prefix from the version.
    $version = preg_replace('/^' . \Drupal::CORE_COMPATIBILITY . '-/', NULL, $project['info']['version']);

    if (preg_match('/-dev$/', $version)) {
      return preg_replace('/^(\d).+-dev$/', '$1.x-dev', $version);
    }

    // If this is extension needs to use 'forced semver', the first digit of its
    // minor version will be treated as the semantic minor version, and the
    // remaining minor version digits will be treated as the semantic patch
    // version.
    $force_semver = in_array(
      $project['name'],
      $this->config->get('force_semver') ?: [],
      TRUE
    );

    if ($force_semver) {
      $matches = [];
      preg_match('/^(\d+).(\d)(\d+)(-.+)?$/', $version, $matches);
      $version = $matches[1] . '.' . $matches[2] . '.' . $matches[3];
      // $matches[4] is only populated if the version has a "-[prerelease]"
      // string at the end, so we must check to see if it exists before
      // appending it back onto the end of the converted string.
      if (isset($matches[4])) {
        $version .= $matches[4];
      }
      return $version;
    }
    else {
      return preg_replace('/^(\d+\.\d+)(-.+)?$/', '$1.0$2', $version);
    }
  }

  protected function getDrupalDependencies(Extension $extension) {
    $composer = $this->readJson($extension->getPath(), 'composer.json');
    if (empty($composer['require'])) {
      return [];
    }

    $drupal_dependencies = [];
    foreach (array_keys($composer['require']) as $package) {
      if (strpos($package, 'drupal/') === 0) {
        $drupal_dependencies[$package] = $package;
      }
    }
    return $drupal_dependencies;
  }

  /**
   * Computes the Composer package name of an extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension object.
   *
   * @return string
   *   The full package name, including the vendor prefix.
   */
  protected function getPackageName(Extension $extension) {
    $extension_name = $extension->getName();

    if ($extension->getType() === 'profile') {
      $info = system_get_info('module', $extension_name);

      if (strtolower($info['distribution']['name']) !== 'drupal') {
        // Profiles provided by core and those that aren't distributions (e.g.
        // custom profiles) will always have their distribution name set to
        // "Drupal" by _system_rebuild_module_data(). All other distributions
        // must provide a composer.json that provides the namespace.
        $composer = $this->readJson($extension->getPath(), 'composer.json');

        if (isset($composer['name'])) {
          return $composer['name'];
        }
        else {
          throw new \LogicException("Distribution '$extension_name' either has no composer.json, or its composer.json does not define a package name.");
        }
      }
    }
    return "drupal/$extension_name";
  }

  /**
   * Returns the extension object for a project.
   *
   * @param array $project
   *   The project info, as returned by UpdateManager::getProjects().
   *
   * @return \Drupal\Core\Extension\Extension
   *   The project's extension object.
   *
   * @throws \InvalidArgumentException if the project is not a module or theme.
   */
  protected function getExtension(array $project) {
    $type = $project['project_type'];
    $name = $project['name'];

    switch ($type) {
      case 'module':
        return $this->moduleHandler->getModule($name);

      case 'theme':
        return $this->themeHandler->getTheme($name);

      default:
        throw new \InvalidArgumentException("Unknown project type: '$type'");
    }
  }

  /**
   * Reads an arbitrary JSON file.
   *
   * @param string $dir
   *   The directory in which the JSON file lives.
   * @param string $file
   *   The name of the JSON file to read.
   *
   * @return bool|mixed
   *   The decoded data in the JSON file, or FALSE if the file does not exist.
   */
  protected function readJson($dir, $file) {
    $file = $dir . DIRECTORY_SEPARATOR . $file;

    if (file_exists($file)) {
      $info = file_get_contents($file);
      return $this->decodeJson($info);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Wraps around Json::decode() to handle JSON errors.
   */
  protected function decodeJson($data) {
    $data = Json::decode($data);

    $code = json_last_error();
    if ($code !== JSON_ERROR_NONE) {
      throw new \RuntimeException(json_last_error_msg(), $code);
    }
    return $data;
  }

}
