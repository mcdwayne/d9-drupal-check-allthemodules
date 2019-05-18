<?php

namespace Drupal\plus;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\plus\Annotation\Theme;
use Drupal\plus\Events\ThemeEvent;
use Drupal\plus\Events\ThemeEvents;
use Drupal\plus\Events\ThemeEventSubscriberInterface;
use Drupal\plus\Plugin\PluginProviderTypeInterface;
use Drupal\plus\Plugin\Theme\ThemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manages discovery and instantiation of "Theme" annotations.
 *
 * @ingroup plugins_alter
 */
class ThemePluginManager extends ProviderPluginManager implements EventSubscriberInterface, FallbackPluginManagerInterface, ThemeEventSubscriberInterface {

  /**
   * The Theme Handler service.
   *
   * @var \Drupal\plus\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * An array of loaded theme plugins.
   *
   * @var \Drupal\plus\Plugin\Theme\ThemeInterface[]
   */
  protected $themes;

  /**
   * The Theme Settings Plugin Manager service.
   *
   * @var \Drupal\plus\SettingPluginManager
   */
  protected $themeSettingsPluginManager;

  /**
   * ThemePluginManager constructor.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface $provider_type
   *   The plugin provider type used for discovery.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The backend cache service to use.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The Theme Handler service.
   * @param \Drupal\plus\SettingPluginManager $theme_settings_plugin_manager
   *   The Theme Settings Plugin Manager service.
   */
  public function __construct(PluginProviderTypeInterface $provider_type, CacheBackendInterface $cache_backend, ThemeHandlerInterface $theme_handler, SettingPluginManager $theme_settings_plugin_manager) {
    parent::__construct($provider_type, 'Plugin/Theme', ThemeInterface::class, Theme::class, $cache_backend);
    $this->themeHandler = $theme_handler;
    $this->themeSettingsPluginManager = $theme_settings_plugin_manager;

    // The plugin manager uses the "theme" provider type, thus it won't
    // discover the "_base" fallback plugin in this module. To get around this,
    // just prepend this specific module's namespace.
    $module_namespace = [\Drupal::root() . '/' . drupal_get_path('module', 'plus') . '/src'];
    $this->namespaces->prepend($module_namespace, 'Drupal\\plus');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.provider.theme'),
      $container->get('cache.discovery'),
      $container->get('theme_handler'),
      $container->get('plugin.manager.setting')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // If this PluginManager has fallback capabilities catch
    // PluginNotFoundExceptions.
    if ($this instanceof FallbackPluginManagerInterface) {
      try {
        return $this->getFactory()->createInstance($plugin_id, $configuration);
      }
      catch (PluginNotFoundException $e) {
        $fallback_id = $this->getFallbackPluginId($plugin_id, $configuration);
        if (strpos($fallback_id, '_base:') === 0) {
          $configuration['theme'] = substr($fallback_id, 6);
          $fallback_id = '_base';
        }
        return $this->getFactory()->createInstance($fallback_id, $configuration);
      }
    }
    else {
      return $this->getFactory()->createInstance($plugin_id, $configuration);
    }
  }

  /**
   * Retrieves an PlusTheme plugin instance for the active theme.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface
   *   A theme object.
   */
  public function getActiveTheme() {
    return $this->getTheme();
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return "_base:$plugin_id";
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ThemeEvents::ACTIVATE][] = ['onThemeActivate', 800];
    $events[ThemeEvents::ACTIVATED][] = ['onThemeActivated', 800];
    $events[ThemeEvents::INSTALL][] = ['onThemeInstall', 800];
    $events[ThemeEvents::INSTALLED][] = ['onThemeInstalled', 800];
    $events[ThemeEvents::UNINSTALL][] = ['onThemeUninstall', 800];
    $events[ThemeEvents::UNINSTALLED][] = ['onThemeUninstalled', 800];
    return $events;
  }

  /**
   * Retrieves an Theme plugin instance for a specific theme.
   *
   * @param string|\Drupal\plus\Plugin\Theme\ThemeInterface|\Drupal\Core\Extension\Extension $theme
   *   The name of a theme, Theme plugin instance or an Extension object. If
   *   not provided, the active theme will be used instead.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface
   *   A theme plugin.
   */
  public function getTheme($theme = NULL) {
    $name = $this->themeHandler->normalizeThemeName($theme);
    if (!isset($this->themes[$name])) {
      $instance = $this->createInstance($name);
      // @todo Create an event that listens to when the active theme changes.
      $instance->initialize();
      $this->themes[$name] = $instance;
    }
    return $this->themes[$name];
  }

  /**
   * Retrieves Theme plugin instances for specified themes.
   *
   * @param string[]|\Drupal\plus\Plugin\Theme\ThemeInterface[]|\Drupal\Core\Extension\Extension[] $themes
   *   An array of theme names, Theme plugin instances or an Extension objects.
   *   If omitted entirely, then all installed themes will be loaded.
   * @param bool $filter
   *   Filters out themes that are not Plus based.
   *
   * @return \Drupal\plus\Plugin\Theme\ThemeInterface[]
   *   An array of theme plugins, keyed by the theme machine name.
   */
  public function getThemes(array $themes = NULL, $filter = TRUE) {
    if (!isset($themes)) {
      $themes = $this->themeHandler->listInfo();
    }

    $objects = [];
    foreach ($themes as $value) {
      $theme = $this->getTheme($value);
      if ($filter && !$theme->isPlus()) {
        continue;
      }
      $objects[$theme->getName()] = $theme;
    }
    return $objects;
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeActivate(ThemeEvent $event) {
    return $this->proxyThemeEvent(__FUNCTION__, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeActivated(ThemeEvent $event) {
    return $this->proxyThemeEvent(__FUNCTION__, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeInstall(ThemeEvent $event) {
    return $this->proxyThemeEvent(__FUNCTION__, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeInstalled(ThemeEvent $event) {
    return $this->proxyThemeEvent(__FUNCTION__, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeUninstall(ThemeEvent $event) {
    return $this->proxyThemeEvent(__FUNCTION__, $event);
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeUninstalled(ThemeEvent $event) {
    return $this->proxyThemeEvent(__FUNCTION__, $event);
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $provider === 'plus' || parent::providerExists($provider);
  }

  /**
   * Proxies an event to all themes.
   *
   * @param string $method
   *   The method to invoke.
   * @param \Drupal\plus\Events\ThemeEvent $event
   *   The ThemeEvent to proxy.
   *
   * @return \Drupal\plus\Events\ThemeEvent
   *   The ThemeEvent object.
   */
  protected function proxyThemeEvent($method, ThemeEvent $event) {
    foreach ($this->getThemes() as $theme) {
      $event = call_user_func_array([$theme, $method], [$event]);
      if ($event->isPropagationStopped()) {
        break;
      }
    }
    return $event;
  }

}
