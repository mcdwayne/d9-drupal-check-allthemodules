<?php

namespace Drupal\panels_extended;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\panels_extended\Form\AdminSettingsForm;
use Psr\Log\LoggerInterface;
use Traversable;

/**
 * Provides a configurable plugin manager for blocks.
 *
 * Uses configuration settings to determine if we want to
 * show all blocks or exclude the ones from specific providers.
 *
 * @see \Drupal\panels_extended\Form\AdminSettingsForm::CFG_EXCLUDE_BLOCKS
 */
class ConfigurableBlockManager extends BlockManager {

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    LoggerInterface $logger,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $logger);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    $definitions = parent::getSortedDefinitions($definitions);

    $excludedProviders = $this->configFactory->get(AdminSettingsForm::CFG_NAME)->get(AdminSettingsForm::CFG_EXCLUDE_BLOCKS) ?: [];
    if (!empty($excludedProviders)) {
      foreach ($definitions as $id => $definition) {
        if (in_array($definition['provider'], $excludedProviders)) {
          unset($definitions[$id]);
        }
      }
    }
    return $definitions;
  }

}
