<?php

namespace Drupal\panels_extended;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManager;
use Drupal\panels_extended\Form\AdminSettingsForm;
use Traversable;

/**
 * Provides a configurable plugin manager for display_builders.
 *
 * Uses configuration settings to determine if we want to
 * show all display builders (used by DisplayVariant extended_panels_variant).
 *
 * @see \Drupal\panels_extended\Form\AdminSettingsForm::CFG_EXCLUDE_DISPLAY_BUILDERS
 */
class ConfigurableDisplayBuilderManager extends DisplayBuilderManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($namespaces, $cache_backend, $module_handler);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(array $definitions = NULL, $label_key = 'label') {
    $definitions = parent::getDefinitions();

    $excluded = $this->configFactory->get(AdminSettingsForm::CFG_NAME)->get(AdminSettingsForm::CFG_EXCLUDE_DISPLAY_BUILDERS) ?: [];
    return array_diff_key($definitions, $excluded);
  }

}
