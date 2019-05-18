<?php

namespace Drupal\mustache\Summable;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\mustache\Exception\MustacheFileException;
use Drupal\mustache\Exception\MustacheTemplateNotFoundException;
use Drupal\mustache\Helpers\MustacheRenderTemplate;
use Drupal\mustache\MustacheTemplates;

/**
 * Class for providing Mustache templates as summable script files.
 */
class SummableScripts implements SummableScriptsInterface {

  /**
   * Whether the usage of summable script files is enabled or not.
   *
   * @var bool
   */
  protected $enabled;

  /**
   * The file path where to store generated script files.
   *
   * @var string
   */
  protected $jsPath;

  /**
   * The cache backend to store summable script information.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The finder of Mustache templates.
   *
   * @var \Drupal\mustache\MustacheTemplates
   */
  protected $templates;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme initialization service.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * A list of libraries for every template as summable script file.
   *
   * @var array
   */
  protected $libraries;

  /**
   * A list of known template libraries currently being used per theme.
   *
   * @var array
   */
  protected $current;

  /**
   * SummableScripts constructor.
   *
   * @param bool $enabled
   *   Whether the usage of summable script files is enabled or not.
   * @param string $js_path
   *   The file path where to store generated script files.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to store summable script information.
   * @param \Drupal\mustache\MustacheTemplates $templates
   *   The finder of Mustache templates.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization service.
   */
  public function __construct($enabled, $js_path, CacheBackendInterface $cache, MustacheTemplates $templates, LibraryDiscoveryInterface $library_discovery, ThemeManagerInterface $theme_manager, ThemeInitializationInterface $theme_initialization) {
    $this->enabled = $enabled;
    $this->jsPath = $js_path;
    $this->cache = $cache;
    $this->templates = $templates;
    $this->libraryDiscovery = $library_discovery;
    $this->themeManager = $theme_manager;
    $this->themeInitialization = $theme_initialization;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryName($template, ActiveTheme $theme = NULL) {
    $library_name = FALSE;
    $theme = $theme ?: $this->themeManager->getActiveTheme();
    $theme_name = $theme->getName();

    $cid = 'mustache:libraries:' . $theme_name;
    $regenerate = FALSE;
    if (!isset($this->current[$theme_name])) {
      if ($cached = $this->cache->get($cid)) {
        $this->current[$theme_name] = $cached->data;
      }
    }
    if (isset($this->current[$theme_name][$template])) {
      $library_name = $this->current[$theme_name][$template];
    }
    else {
      $regenerate = TRUE;
      $candidates = ['template.' . $theme_name . '.' . $template];
      foreach ($theme->getBaseThemes() as $base_theme) {
        $candidates[] = 'template.' . $base_theme->getName() . '.' . $template;
      }
      $candidates[] = 'template.module.' . $template;
      foreach ($candidates as $candidate) {
        if ($this->libraryDiscovery->getLibraryByName('mustache', $candidate)) {
          $library_name = 'mustache/' . $candidate;
          break;
        }
      }
      $this->current[$theme_name][$template] = $library_name;
      $this->cache->set($cid, $this->current[$theme_name]);
    }
    if (!$library_name) {
      throw new MustacheFileException(t('Cannot provide summable script file, because no library has been found for template @template.', ['@template' => $template]));
    }

    // Make sure the script file has been generated and is available.
    if (!$this->generate($library_name, $regenerate)) {
      throw new MustacheFileException(t('Failed to generate summable script file for template @template', ['@template' => $template]));
    }

    return $library_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllLibraries() {
    if (!isset($this->libraries)) {
      $cid = 'mustache:libraries';
      if ($cached = $this->cache->get($cid)) {
        $this->libraries = $cached->data;
      }
      else {
        $libraries = [];

        foreach ($this->templates->findAll() as $name => $items) {
          foreach ($items as $item) {
            $library_name = 'template.' . $item['provider'] . '.' . $name;
            $js_file_uri = $this->buildUri($item['provider'], $name);
            $js_file_path = file_url_transform_relative(file_create_url($js_file_uri));
            $libraries[$library_name] = [
              'js' => [$js_file_path => []],
              'dependencies' => ['mustache/sync'],
            ];
          }
        }

        $this->cache->set($cid, $libraries);
        $this->libraries = $libraries;
      }
    }

    return $this->libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function buildUri($provider, $template_name) {
    return str_replace('-', '_', $this->jsPath . '/' . $provider . '/' . $template_name . '.js');
  }

  /**
   * {@inheritdoc}
   */
  public function generate($library_name, $regenerate = FALSE) {
    $parts = explode('.', $library_name);
    $provider = $parts[1];
    $template_name = $parts[2];

    $script_file_uri = $this->buildUri($provider, $template_name);
    if (file_exists($script_file_uri)) {
      if (!$regenerate) {
        return TRUE;
      }
    }

    $template_file = NULL;
    if ($provider === 'module') {
      $module_templates = $this->templates->getModuleTemplates();
      if (isset($module_templates[$template_name]['file'])) {
        $template_file = $module_templates[$template_name]['file'];
      }
    }
    else {
      $theme = $this->themeManager->getActiveTheme();
      if ($theme->getName() !== $provider) {
        $theme = $this->themeInitialization->getActiveThemeByName($provider);
      }
      $template_file = $this->templates->find($template_name, $theme);
    }
    if (!isset($template_file) || !file_exists($template_file)) {
      throw new MustacheTemplateNotFoundException(t('Defined template @template for library @library not found.', ['@template' => $template_name, '@library' => $library_name]));
    }
    $template_content = file_get_contents($template_file);
    $template_encoded = trim(substr(json_encode($template_content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), 1, -1));

    $build = MustacheRenderTemplate::build('summable')
      ->usingData([
        'name' => $template_name,
        'content' => $template_encoded,
      ])
      ->toRenderArray();
    $script_generated = trim((string) render($build));

    $directory = $this->jsPath . '/' . $provider;
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    if (file_unmanaged_save_data($script_generated, $script_file_uri, FILE_EXISTS_REPLACE) === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return file_unmanaged_delete_recursive($this->jsPath);
  }

}
