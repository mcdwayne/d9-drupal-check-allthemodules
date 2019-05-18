<?php

namespace Drupal\plus;

use Drupal\plus\Plugin\Provider\ProviderInterface;
use Drupal\plus\Plugin\Theme\ThemeInterface;

/**
 * Manages discovery and instantiation of Bootstrap CDN providers.
 *
 * @ingroup plugins_provider
 */
class ProviderManagerProvider extends ProviderPluginManager {
  /**
   * The base file system path for CDN providers.
   *
   * @var string
   */
  const FILE_PATH = 'public://bootstrap/provider';

  /**
   * Constructs a new \Drupal\plus\Plugin\ProviderManagerProvider object.
   *
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The theme to use for discovery.
   */
  public function __construct(ThemeInterface $theme) {
    parent::__construct('Plugin/Provider', 'Drupal\plus\Plugin\Provider\ProviderInterface', 'Drupal\plus\Annotation\PlusProvider', $theme->getExtension());
    $this->alterInfo('plus_provider_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    /** @var ProviderInterface $provider */
    $provider = new $definition['class'](['extension' => $this->extension], $plugin_id, $definition);
    $provider->processDefinition($definition, $plugin_id);
  }

}
