<?php

namespace Drupal\plus\Plugin;

use Drupal\plus\Plus;

/**
 * Base class for an theme aware plugins.
 *
 * @ingroup utility
 */
class ThemePluginBase extends PluginBase {

  /**
   * The currently set theme object.
   *
   * @var \Drupal\plus\Plugin\Theme\ThemeInterface
   */
  protected $theme;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (isset($configuration['extension']) && !isset($configuration['theme'])) {
      $configuration['theme'] = Plus::getTheme($configuration['extension']);
    }
    $this->theme = isset($configuration['theme']) ? Plus::getTheme($configuration['theme']) : Plus::getActiveTheme();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
