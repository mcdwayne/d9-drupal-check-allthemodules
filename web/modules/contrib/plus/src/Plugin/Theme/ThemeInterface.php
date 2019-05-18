<?php

namespace Drupal\plus\Plugin\Theme;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\plus\Events\ThemeEventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface ThemeInterface.
 */
interface ThemeInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface, ThemeEventSubscriberInterface {

  /**
   * Adds a callback to an array.
   *
   * @param array $callbacks
   *   An array of callbacks to add the callback to, passed by reference.
   * @param array|string $callback
   *   The callback to add.
   * @param array|string $replace
   *   If specified, the callback will instead replace the specified value
   *   instead of being appended to the $callbacks array.
   * @param string $placement
   *   Flag that determines how to add the callback to the array.
   *
   * @return bool
   *   TRUE if the callback was added, FALSE if $replace was specified but its
   *   callback could be found in the list of callbacks.
   *
   * @throws \InvalidArgumentException
   *   If the $placement is not a valid type.
   */
  public function addCallback(array &$callbacks, $callback, $replace = NULL, $placement = 'append');

  /**
   * Returns the autoload fix include path.
   *
   * This method assists class based callbacks that normally do not work.
   *
   * If you notice that your class based callback is never invoked, you may try
   * using this helper method as an "include" or "file" for your callback, if
   * the callback metadata supports such an option.
   *
   * Depending on when or where a callback is invoked during a request, such as
   * an ajax or batch request, the theme handler may not yet be fully
   * initialized.
   *
   * Typically there is little that can be done about this "issue" from core.
   * It must balance the appropriate level that should be bootstrapped along
   * with common functionality. Cross-request class based callbacks are not
   * common in themes.
   *
   * When this file is included, it will attempt to jump start this process.
   *
   * Please keep in mind, that it is merely an attempt and does not guarantee
   * that it will actually work. If it does not appear to work, do not use it.
   *
   * @see \Drupal\Core\Extension\ThemeHandler::listInfo
   * @see \Drupal\Core\Extension\ThemeHandler::systemThemeList
   * @see system_list
   * @see system_register()
   * @see drupal_classloader_register()
   *
   * @return string
   *   The autoload fix include path, relative to Drupal root.
   */
  public function autoloadFixInclude();

  /**
   * Retrieves the documentation URL, if any.
   *
   * @param bool $search
   *   Flag indicating whether or not to retrieve the documentation URL used
   *   for searching/querying.
   *
   * @return string
   *   The documentation URL.
   */
  public function documentationUrl($search = FALSE);

  /**
   * Provides additional default variables to be used in elements and templates.
   *
   * @return array
   *   An associative array containing key/default value pairs.
   */
  public function defaultVariables();

  /**
   * Logs and displays a warning about a deprecated function/method being used.
   */
  public function deprecated();

  /**
   * Manages theme alter hooks as classes and allows sub-themes to sub-class.
   *
   * @param string $function
   *   The procedural function name of the alter (e.g. __FUNCTION__).
   * @param mixed $data
   *   The variable that was passed to the hook_TYPE_alter() implementation to
   *   be altered. The type of this variable depends on the value of the $type
   *   argument. For example, when altering a 'form', $data will be a structured
   *   array. When altering a 'profile', $data will be an object.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference. If more
   *   context needs to be provided to implementations, then this should be an
   *   associative array as described above.
   */
  public function doAlter($function, &$data, &$context1 = NULL, &$context2 = NULL);

  /**
   * Retrieves the theme's settings array appropriate for drupalSettings.
   *
   * @return array
   *   The theme settings for drupalSettings.
   */
  public function drupalSettings();

  /**
   * Wrapper for the core file_scan_directory() function.
   *
   * Finds all files that match a given mask in the given directories and then
   * caches the results. A general site cache clear will force new scans to be
   * initiated for already cached directories.
   *
   * @param string $mask
   *   The preg_match() regular expression of the files to find.
   * @param string $subdir
   *   Sub-directory in the theme to start the scan, without trailing slash. If
   *   not set, the base path of the current theme will be used.
   * @param array $options
   *   Options to pass, see file_scan_directory() for addition options:
   *   - ignore_flags: (int|FALSE) A bitmask to indicate which directories (if
   *     any) should be skipped during the scan. Must also not contain a
   *     "nomask" property in $options. Value can be any of the following:
   *     - \Drupal\plus::IGNORE_CORE
   *     - \Drupal\plus::IGNORE_ASSETS
   *     - \Drupal\plus::IGNORE_DOCS
   *     - \Drupal\plus::IGNORE_DEV
   *     - \Drupal\plus::IGNORE_THEME
   *     Pass FALSE to iterate over all directories in $dir.
   *
   * @return array
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' members corresponding to the matching files.
   *
   * @see file_scan_directory()
   */
  public function fileScan($mask, $subdir = NULL, array $options = []);

  /**
   * Retrieves the alter plugin manager for the theme.
   *
   * @return \Drupal\plus\AlterManager
   *   The alter plugin manager.
   */
  public function getAlterManager();

  /**
   * Retrieves the full base/sub-theme ancestry of a theme.
   *
   * @param bool $reverse
   *   Whether or not to return the array of themes in reverse order, where the
   *   active theme is the first entry.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface[]
   *   An associative array of theme plugin instances, keyed by machine name.
   */
  public function getAncestry($reverse = FALSE);

  /**
   * Retrieves an individual item from a theme's cache in the database.
   *
   * @param string $name
   *   The name of the item to retrieve from the theme cache.
   * @param array $context
   *   Optional. An array of additional context to use for retrieving the
   *   cached storage.
   * @param mixed $default
   *   Optional. The default value to use if $name does not exist.
   *
   * @return mixed|\Drupal\plus\Utility\StorageItem
   *   The cached value for $name.
   */
  public function getCache($name, array $context = [], $default = []);

  /**
   * Retrieves the class to use for constructing new Element instances.
   *
   * @return string
   *   The class name.
   */
  public function getElementClass();

  /**
   * The Extension object for this theme.
   *
   * @return \Drupal\Core\Extension\Extension
   */
  public function getExtension();

  /**
   * Retrieves the theme info.
   *
   * @param string $property
   *   A specific property entry from the theme's info array to return.
   * @param mixed $default
   *   The default value to return if not set.
   *
   * @return array
   *   The entire theme info or a specific item if $property was passed.
   */
  public function getInfo($property = NULL, $default = NULL);

  /**
   * Returns the machine name of the theme.
   *
   * @return string
   *   The machine name of the theme.
   */
  public function getName();

  /**
   * Returns the relative path of the theme.
   *
   * @return string
   *   The relative path of the theme.
   */
  public function getPath();

  /**
   * Retrieves pending updates for the theme.
   *
   * @return \Drupal\plus\Plugin\Update\UpdateInterface[]
   *   An array of update plugin objects.
   */
  public function getPendingUpdates();

  /**
   * Retrieves a theme setting.
   *
   * @param string $name
   *   The name of the setting to be retrieved.
   * @param mixed $default
   *   A default value to provide if the setting is not found or if the plugin
   *   does not have a "defaultValue" annotation key/value pair. Typically,
   *   you will likely never need to use this unless in rare circumstances
   *   where the setting plugin exists but needs a default value not able to
   *   be set by conventional means (e.g. empty array).
   *
   * @return mixed
   *   The value of the requested setting, NULL if the setting does not exist
   *   and no $default value was provided.
   *
   * @see theme_get_setting()
   */
  public function getSetting($name, $default = NULL);

  /**
   * Retrieves the setting plugin manager for the theme.
   *
   * @return \Drupal\plus\SettingPluginManager
   *   The setting plugin manager.
   */
  public function getSettingManager();

  /**
   * Retrieves a theme's setting plugin instance(s).
   *
   * @param string $name
   *   Optional. The name of a specific setting plugin instance to return.
   *
   * @return \Drupal\plus\Plugin\Setting\SettingInterface|\Drupal\plus\Plugin\Setting\SettingInterface[]|NULL
   *   If $name was provided, it will either return a specific setting plugin
   *   instance or NULL if not set. If $name was omitted it will return an array
   *   of setting plugin instances, keyed by their name.
   */
  public function getSettingPlugin($name = NULL);

  /**
   * Retrieves the theme's cache from the database.
   *
   * @return \Drupal\plus\Utility\Storage
   *   The cache object.
   */
  public function getStorage();

  /**
   * Retrieves the template plugin manager for the theme.
   *
   * @return \Drupal\plus\Plugin\Theme\Template\ProviderPluginManager
   *   The template plugin manager.
   */
  public function getTemplateManager();

  /**
   * Returns any the theme hook definition information.
   *
   * @param array $existing
   *   The existing theme hook registry info.
   * @param $type
   *   The extension type.
   * @param $theme
   *   The extension machine name.
   * @param $path
   *   The extension file system path.
   *
   * @return array
   *   An associative array that mimics hook_theme().
   *
   * @see \Drupal\plus\Plugin\Alter\ThemeRegistry::alter()
   * @see plus_theme_registry_alter()
   * @see plus_theme()
   * @see hook_theme()
   */
  public function getThemeHooks($existing, $type, $theme, $path);

  /**
   * Retrieves the human-readable title of the theme.
   *
   * @return string
   *   The theme title or machine name as a fallback.
   */
  public function getTitle();

  /**
   * Retrieves the update plugin manager for the theme.
   *
   * @return \Drupal\plus\UpdateManagerProvider
   *   The update plugin manager.
   */
  public function getUpdateManager();

  /**
   * Includes a file from the theme.
   *
   * @param string $file
   *   The file name, including the extension.
   * @param string $subdir
   *   A subdirectory inside the theme. Defaults to: "includes". Set to FALSE
   *   or and empty string if the file resides in the theme's root directory.
   *
   * @return bool
   *   TRUE if the file exists and is included successfully, FALSE otherwise.
   */
  public function includeOnce($file, $subdir = 'includes');

  /**
   * Initializes the theme.
   */
  public function initialize();

  /**
   * Installs a theme.
   */
  public function install();

  /**
   * Indicates whether this theme is an Plus based theme.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isPlus();

  /**
   * Indicates whether the theme is in "development mode".
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @see \Drupal\plus\Theme::dev
   */
  public function isDev();

  /**
   * Returns the livereload URL set, if any.
   *
   * @return string
   *
   * @see \Drupal\plus\Theme::livereload
   */
  public function livereloadUrl();

  /**
   * Provides additional variables to be used in templates.
   *
   * @return array
   *   An associative array containing key/default value pairs.
   */
  public function preprocessVariables();

  /**
   * Removes a theme setting.
   *
   * @param string $name
   *   Name of the theme setting to remove.
   */
  public function removeSetting($name);

  /**
   * Sets a value for a theme setting.
   *
   * @param string $name
   *   Name of the theme setting.
   * @param mixed $value
   *   Value to associate with the theme setting.
   */
  public function setSetting($name, $value);

  /**
   * Retrieves the theme settings instance.
   *
   * @return \Drupal\plus\ThemeSettings
   *   All settings.
   */
  public function settings();

  /**
   * Determines whether or not a theme is a sub-theme of another.
   *
   * @param string|\Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The name or theme Extension object to check.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function subthemeOf($theme);

  /**
   * Uninstalls a theme.
   */
  public function uninstall();

}
