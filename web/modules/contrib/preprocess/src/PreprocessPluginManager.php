<?php

namespace Drupal\preprocess;

use Drupal\preprocess\Annotation\Preprocess;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Manages @Preprocess plugins.
 *
 * @package Drupal\preprocess
 */
class PreprocessPluginManager extends DefaultPluginManager implements PreprocessPluginManagerInterface {

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
   * PreprocessPluginManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler to invoke the alter hook with.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager) {
    parent::__construct('Plugin/Preprocess', $namespaces, $module_handler, PreprocessInterface::class, Preprocess::class);
    $this->setCacheBackend($cache_backend, 'preprocess_plugins');
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(): array {
    /** @var \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[] $definitions */
    $definitions = parent::getDefinitions();

    // Make sure definitions in themes are only used if that theme is active.
    foreach ($definitions as $definition) {
      $provider = $definition['provider'];
      if (!$this->themeHandler->themeExists($provider)) {
        continue;
      }

      $theme = $this->themeManager->getActiveTheme();
      if ($theme->getName() !== $provider) {
        unset($definitions[$definition['id']]);
      }
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions(): array {
    $definitions = parent::findDefinitions();
    uasort($definitions, function ($a, $b) {
      $a_is_theme = $this->themeHandler->themeExists($a['provider']);
      $b_is_theme = $this->themeHandler->themeExists($b['provider']);

      if ($a_is_theme === $b_is_theme) {
        return strcmp($a['id'], $b['id']);
      }
      return $a_is_theme < $b_is_theme ? -1 : 1;
    });

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreprocessors(string $hook): array {
    if (!$this->hasPreprocessors()) {
      return [];
    }

    static $preprocessors = [];
    if (isset($preprocessors[$hook])) {
      return $preprocessors[$hook];
    }

    /** @var \Drupal\Component\Plugin\Definition\PluginDefinitionInterface[] $definitions */
    $definitions = \array_filter($this->getDefinitions(), function ($definition) use ($hook) {
      return $definition['hook'] === $hook;
    });

    $preprocessors[$hook] = [];
    foreach ($definitions as $definition) {
      $preprocessors[$hook][] = $this->createInstance($definition['id']);
    }

    return $preprocessors[$hook];
  }

  /**
   * {@inheritdoc}
   */
  public function hasPreprocessors(): bool {
    static $has_preprocessors = NULL;

    if ($has_preprocessors !== NULL) {
      return $has_preprocessors;
    }

    $has_preprocessors = !empty($this->getDefinitions());
    return $has_preprocessors;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!$this->discovery) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      $discovery = new YamlDiscoveryDecorator($discovery, 'preprocessors', \array_merge($this->moduleHandler->getModuleDirectories(), $this->themeHandler->getThemeDirectories()));
      $discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
      $this->discovery = $discovery;
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider): bool {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

}
