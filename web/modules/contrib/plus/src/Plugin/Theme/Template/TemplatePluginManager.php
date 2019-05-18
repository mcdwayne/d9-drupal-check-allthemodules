<?php

namespace Drupal\plus\Plugin\Theme\Template;

use Drupal\plus\ProviderPluginManager as BasePluginManager;
use Drupal\plus\Plugin\Theme\ThemeInterface;

/**
 * Manages discovery and instantiation of theme templates.
 *
 * @ingroup plugins_preprocess
 */
class TemplatePluginManager extends BasePluginManager {

  /**
   * Constructs a new ProviderPluginManager object.
   *
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The theme to use for discovery.
   */
  public function __construct(ThemeInterface $theme) {
    parent::__construct('Plugin/Theme/Template', 'Drupal\plus\Plugin\Theme\Template\TemplateInterface', 'Drupal\plus\Annotation\Template', $theme->getExtension());
    $this->alterInfo('template_plugins');
  }

}
