<?php

namespace Drupal\paragraphs_collection;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides common helper methods for style discovery.
 * @todo Create documentation for style discovery https://www.drupal.org/node/2837995
 */
class StyleDiscovery implements StyleDiscoveryInterface {

  use StringTranslationTrait;

  /**
   * Collection of styles with its definition.
   *
   * @var array
   */
  protected $stylesCollection;

  /**
   * Collection of style groups with their definition.
   *
   * @var array
   */
  protected $groupCollection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new YamlStyleDiscovery.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $string_translation, ControllerResolverInterface $controller_resolver, CacheBackendInterface $cache_backend, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config, AccountProxyInterface $current_user) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->stringTranslation = $string_translation;
    $this->controllerResolver = $controller_resolver;
    $this->cache = $cache_backend;
    $this->configFactory = $config;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleOptions($group = '', $access_check = FALSE) {
    $options = [];

    $style_collection = $this->getStyles();
    $enabled_styles = $this->configFactory->get('paragraphs_collection.settings')->get('enabled_styles');
    foreach ($style_collection as $style => $definition) {
      if (empty($enabled_styles) || in_array($style, $enabled_styles)) {
        if (empty($group) || in_array($group, $definition['groups'])) {
          // Filter the styles based on whether the current user has access.
          if ($access_check && !$this->isAllowedAccess($definition)) {
            continue;
          }

          $options[$style] = $definition['title'];
        }
      }
    };
    uasort($options, 'strcasecmp');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles() {
    $cache_id = 'paragraphs_collection_style';
    if ($this->stylesCollection !== NULL) {
      return $this->stylesCollection;
    }
    else if ($cached = $this->cache->get($cache_id)) {
      $this->stylesCollection = $cached->data;
    }
    else {
      $yaml_discovery = $this->getYamlDiscovery();
      $this->stylesCollection = [];
      foreach ($yaml_discovery->findAll() as $module => $styles) {
        foreach ($styles as $style => $definition) {
          if (empty($definition['title'])) {
            throw new InvalidStyleException('The "title" of "' . $style . '" must be non-empty.');
          }
          $definition['title'] = $this->t($definition['title']);
          if (!empty($definition['description'])) {
            $definition['description'] = $this->t($definition['description']);
          }
          $this->stylesCollection[$style] = ['name' => $style];
          $this->stylesCollection[$style] += $definition + ['libraries' => []];
        }
      }
      $this->cache->set($cache_id, $this->stylesCollection);
    }
    return $this->stylesCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle($style, $default = NULL) {
    $styles = $this->getStyles();
    $enabled_styles = $this->configFactory->get('paragraphs_collection.settings')->get('enabled_styles');
    if ($style && empty($enabled_styles) || in_array($style, $enabled_styles)) {
      if (isset($styles[$style])) {
        return $styles[$style];
      }
    }

    if ($default && empty($enabled_styles) || in_array($default, $enabled_styles)) {
      if (isset($styles[$default])) {
        return $styles[$default];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowedAccess(array $style_definition, AccountProxyInterface $account = NULL) {
    // The style does not define permission property. Allow access.
    if (empty($style_definition['permission']) || $style_definition['permission'] !== TRUE) {
      return TRUE;
    }

    $account = $account ?: $this->currentUser;
    $style_permission = 'use ' . $style_definition['name'] . ' style';

    // Check whether the user has a dedicated style permission.
    return $account->hasPermission($style_permission);
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Core\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery() {
    return new YamlDiscovery('paragraphs.style', $this->moduleHandler->getModuleDirectories() + $this->getSortedThemeDirectories());
  }

  /**
   * Gets the theme directories sorted by hierarchy.
   *
   * @return string[]
   *   The sorted theme directories array.
   */
  protected function getSortedThemeDirectories() {
    $theme_directories = $this->themeHandler->getThemeDirectories();
    $themes = $this->themeHandler->listInfo();
    $sorted_themes = [];
    // Loop over all themes and loop over their base themes.
    foreach ($themes as $theme) {
      if (isset($theme->base_themes)) {
        foreach (array_keys($theme->base_themes) as $base_theme) {
          // If the theme has not been added yet, add it.
          if (!isset($sorted_themes[$base_theme])) {
            $sorted_themes[$base_theme] = TRUE;
          }
        }
      }
      // If the theme has not been added yet, add it.
      if (!isset($sorted_themes[$theme->getName()])) {
        $sorted_themes[$theme->getName()] = TRUE;
      }
    }
    // Sort the theme directories based on the theme keys.
    return array_replace($sorted_themes, $theme_directories);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($style) {
    $collection = $this->getStyles();
    return $collection[$style]['libraries'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleGroups() {
    $cache_id = 'paragraphs_collection_style_group';
    if ($this->groupCollection !== NULL) {
      return $this->groupCollection;
    }
    else if ($cached = $this->cache->get($cache_id)) {
      $this->groupCollection = $cached->data;
    }
    else {
      $yaml_discovery = $this->getYamlGroupDiscovery();
      $this->groupCollection = [];
      foreach ($yaml_discovery->findAll() as $module => $groups) {
        foreach ($groups as $group => $definition) {
          if (empty($definition['label'])) {
            throw new InvalidStyleException('The "label" of "' . $group . '" must be non-empty.');
          }
          $this->groupCollection[$group] = [
            'label' => $this->t($definition['label']),
            'widget_label' => isset($definition['widget_label']) ? $this->t($definition['widget_label']) : NULL
          ];
        }
      }
      $this->cache->set($cache_id, $this->groupCollection);
    }
    return $this->groupCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyleGroupsLabel() {
    $groups = $this->getStyleGroups();
    $group_options = [];
    foreach ($groups as $group_id => $group) {
      $group_options[$group_id] = $group['label'];
    }
    return $group_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel($group_id) {
    $groups = $this->getStyleGroups();
    if (isset($groups[$group_id])) {
      return $groups[$group_id]['label'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupWidgetLabel($group_id) {
    $groups = $this->getStyleGroups();
    if (isset($groups[$group_id])) {
      // If the widget label is empty use the general label and add a "style
      // suffix".
      if ($widget_label = $groups[$group_id]['widget_label']) {
        return $widget_label;
      }
      else {
        return $this->t('@group_label style', ['@group_label' => $this->getGroupLabel($group_id)]);
      }
    }
    return NULL;
  }

  /**
   * Gets the YAML group discovery.
   *
   * @return \Drupal\Core\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlGroupDiscovery() {
    return new YamlDiscovery('paragraphs.style_group', $this->moduleHandler->getModuleDirectories() + $this->getSortedThemeDirectories());
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->stylesCollection = NULL;
    $this->cache->deleteMultiple(['paragraphs_collection_style', 'paragraphs_collection_style_group']);
  }

}
