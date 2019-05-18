<?php

namespace Drupal\plus;

use Drupal\plus\Plugin\Theme\ThemeInterface;
use Drupal\plus\Utility\Element;

/**
 * Manages discovery and instantiation of Bootstrap pre-render callbacks.
 *
 * @ingroup plugins_prerender
 */
class PrerenderManagerProvider extends ProviderPluginManager {

  /**
   * Constructs a new \Drupal\plus\Plugin\PrerenderManagerProvider object.
   *
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The theme to use for discovery.
   */
  public function __construct(ThemeInterface $theme) {
    parent::__construct('Plugin/Prerender', 'Drupal\plus\Plugin\Prerender\PrerenderInterface', 'Drupal\plus\Annotation\PlusPrerender', $theme->getExtension());
    $this->alterInfo('plus_pre_render_plugins');
  }

  /**
   * Pre-render render array element callback.
   *
   * @param array $element
   *   The render array element.
   *
   * @return array
   *   The modified render array element.
   */
  public static function preRender(array $element) {
    if (!empty($element['#bootstrap_ignore_pre_render'])) {
      return $element;
    }

    $e = Element::reference($element);

    if ($e->isType('machine_name')) {
      $e->addClass('form-inline', 'wrapper_attributes');
    }

    // Add smart descriptions to the element, if necessary.
    $e->smartDescription();

    return $element;
  }

}
