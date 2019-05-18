<?php

namespace Drupal\mustache;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\mustache\Exception\MustacheTemplateNotFoundException;

/**
 * The finder of Mustache templates.
 */
class MustacheTemplates {

  /**
   * A list of known, currently used template file paths.
   *
   * @var string[]
   */
  protected $templates;

  /**
   * A collection of already scanned files per extension.
   *
   * @var array
   */
  protected $scanned;

  /**
   * In-memory hold of the last template content.
   *
   * @var string[]
   */
  protected $cachedContent = [];

  /**
   * Template locations defined by modules.
   *
   * @var array
   */
  protected $moduleTemplates;

  /**
   * The cache backend to store template path information.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * MustacheTemplates constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to store template path information.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(CacheBackendInterface $cache, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager) {
    $this->cache = $cache;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * Get the file content for the given template name.
   *
   * @param string $name
   *   The name of the template, without file ending.
   *
   * @return string
   *   The content of the template.
   *
   * @throws \Drupal\mustache\Exception\MustacheTemplateNotFoundException
   *   In case the template file could not be found.
   */
  public function getContent($name) {
    if (isset($this->cachedContent[$name])) {
      return $this->cachedContent[$name];
    }
    if (count($this->cachedContent) > 3) {
      // Just cache a limited set of file contents.
      $this->cachedContent = [];
    }
    $content = file_get_contents($this->find($name));
    $this->cachedContent[$name] = $content;
    return $content;
  }

  /**
   * Looks up the file location for the given template name.
   *
   * @param string $name
   *   The name of the template, without file ending.
   * @param \Drupal\Core\Theme\ActiveTheme|null $theme
   *   (Optional) When given, the lookup is based on this theme.
   *   By default, the lookup uses the currently active theme.
   *
   * @return string
   *   The file path as string if found.
   *
   * @throws \Drupal\mustache\Exception\MustacheTemplateNotFoundException
   *   In case the template file could not be found.
   */
  public function find($name, ActiveTheme $theme = NULL) {
    $theme = $theme ?: $this->themeManager->getActiveTheme();
    $cid = 'mustache:' . $theme->getName() . ':' . $name;
    if (isset($this->templates[$cid])) {
      return $this->templates[$cid];
    }

    if ($cached = $this->cache->get($cid)) {
      if (file_exists($cached->data)) {
        $this->templates[$cid] = $cached->data;
        return $this->templates[$cid];
      }
    }

    $template_file = $this->findTemplateInExtension('theme', $theme->getName(), $name);

    if (!isset($template_file)) {
      foreach ($theme->getBaseThemes() as $base_theme) {
        if ($template_file = $this->findTemplateInExtension('theme', $base_theme->getName(), $name)) {
          break;
        }
      }
    }

    if (!isset($template_file)) {
      $module_templates = $this->getModuleTemplates();
      if (isset($module_templates[$name]['file'])) {
        $template_file = $module_templates[$name]['file'];
      }
    }

    if (!isset($template_file)) {
      throw new MustacheTemplateNotFoundException(t('Mustache template not found for name @name.', ['@name' => $name]));
    }
    elseif (!file_exists($template_file)) {
      throw new MustacheTemplateNotFoundException(t('The registered file @file for the Mustache template @name could not be found.', ['@file' => $template_file, '@name' => $name]));
    }

    $this->templates[$cid] = $template_file;
    $this->cache->set($cid, $template_file);
    return $template_file;
  }

  /**
   * Collects and returns all possible templates.
   *
   * For each template name, multiple files might exist.
   * If you only want to know which template file is currently
   * being used for a given template name, use ::find() instead.
   *
   * @return array
   *   An associative array of items, grouped by template name.
   *   Each item is an array holding the following information
   *   about the found template file:
   *   - file: A string as the path of the template file.
   *   - provider: A string with the value 'module' when the template
   *     has been registered by a module, or the name of the providing theme.
   *   - active: A boolean indicating whether the item equals
   *     the file currently being used, as ::find() would return.
   */
  public function findAll() {
    $templates = [];

    foreach ($this->getModuleTemplates() as $name => $info) {
      $template_file = $info['file'];
      $templates[$name][] = [
        'file' => $template_file,
        'provider' => 'module',
        'active' => ($template_file === $this->find($name)),
      ];
    }

    foreach (array_keys($this->themeHandler->listInfo()) as $theme_name) {
      foreach ($this->findAllTemplatesInExtension('theme', $theme_name) as $scanned) {
        $name = str_replace('.mustache', '', $scanned->name);
        $name = str_replace('-', '_', $name);
        $templates[$name][] = [
          'file' => $scanned->uri,
          'provider' => $theme_name,
          'active' => ($scanned->uri === $this->find($name)),
        ];
      }
    }

    return $templates;
  }

  /**
   * Collects and returns templates defined by modules.
   *
   * @return array
   *   The list of collected module templates.
   */
  public function getModuleTemplates() {
    if (!isset($this->moduleTemplates)) {
      $cid = 'mustache:module_templates';
      if ($cached = $this->cache->get($cid)) {
        $this->moduleTemplates = $cached->data;
      }
      else {
        $this->moduleTemplates = $this->moduleHandler->invokeAll('mustache_templates');
        $this->moduleHandler->alter('mustache_templates', $this->moduleTemplates);
        if (!isset($this->moduleTemplates)) {
          $this->moduleTemplates = [];
        }
        foreach ($this->moduleTemplates as &$info) {
          if (isset($info['default'])) {
            // Convert url objects to strings for serialization.
            if (isset($info['default']['#data']) && ($info['default']['#data'] instanceof Url)) {
              /** @var \Drupal\Core\Url $url */
              $url = clone $info['default']['#data'];
              $info['default']['#data'] = $url->setAbsolute($url->isExternal())->toString();
            }
            if (isset($info['default']['#sync']['data']) && ($info['default']['#sync']['data'] instanceof Url)) {
              /** @var \Drupal\Core\Url $url */
              $url = clone $info['default']['#sync']['data'];
              $info['default']['#sync']['data'] = $url->setAbsolute($url->isExternal())->toString();
            }
          }
        }
        $this->cache->set($cid, $this->moduleTemplates);
      }
    }
    return $this->moduleTemplates;
  }

  /**
   * Try to find the template file for the name inside the given extension.
   *
   * @param string $type
   *   The extension type, either module or theme.
   * @param string $extension_name
   *   The extension name.
   * @param string $template_name
   *   The template name, without file ending.
   *
   * @return string|null
   *   The file uri if found, NULL otherwise.
   */
  public function findTemplateInExtension($type, $extension_name, $template_name) {
    $filename = $template_name . '.mustache';
    $filename_hyphen = str_replace('_', '-', $template_name) . '.mustache';

    foreach ($this->findAllTemplatesInExtension($type, $extension_name) as $scanned) {
      if (($scanned->name == $filename) || ($scanned->name == $filename_hyphen)) {
        return $scanned->uri;
      }
    }

    return NULL;
  }

  /**
   * Try to find all template files at the given extension.
   *
   * @param string $type
   *   The extension type, either module or theme.
   * @param string $extension_name
   *   The extension name.
   *
   * @return array
   *   The list of found template files at the extension.
   */
  public function findAllTemplatesInExtension($type, $extension_name) {
    if (!isset($this->scanned[$extension_name])) {
      $cid = 'mustache:templates:' . $extension_name;
      if ($cached = $this->cache->get($cid)) {
        $this->scanned[$extension_name] = $cached->data;
      }
      else {
        $this->scanned[$extension_name] = file_scan_directory(drupal_get_path($type, $extension_name) . '/templates', '/\.mustache\.tpl$/');
        $this->cache->set($cid, $this->scanned[$extension_name]);
      }
    }
    return $this->scanned[$extension_name];
  }

  /**
   * Get default values for building the render element.
   *
   * @param string $name
   *   The name of the template.
   *
   * @return array
   *   The default values for the render element.
   */
  public function getElementDefaults($name) {
    if (!isset($this->moduleTemplates)) {
      $this->getModuleTemplates();
    }
    if (isset($this->moduleTemplates[$name]['default'])) {
      return $this->moduleTemplates[$name]['default'];
    }
    return [];
  }

}
