<?php

namespace Drupal\arb_token;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class ArbitraryTokenPluginManager.
 */
class ArbitraryTokenPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    parent::__construct(
      'Plugin/arb_token',
      $namespaces,
      $module_handler,
      'Drupal\arb_token\ArbitraryTokenPluginInterface',
      'Drupal\arb_token\Annotation\ArbitraryToken'
    );
    $this->stringTranslation = $string_translation;
    $this->alterInfo('arb_token');
    $this->setCacheBackend($cache_backend, 'arb_token_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    /** @var \Drupal\arb_token\ArbitraryTokenPluginInterface $plugin */
    $plugin = parent::createInstance($plugin_id, $configuration);
    return $plugin->setStringTranslation($this->stringTranslation);
  }

}
