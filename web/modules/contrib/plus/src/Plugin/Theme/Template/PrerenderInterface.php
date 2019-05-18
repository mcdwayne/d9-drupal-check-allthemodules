<?php

namespace Drupal\plus\Plugin\Theme\Template;

use Drupal\plus\Utility\Element;

/**
 * Defines the interface for a #pre_render callback on a "Template" plugin.
 *
 * @ingroup plugins_template
 */
interface PrerenderInterface {

  /**
   * Pre-render element callback.
   *
   * @param \Drupal\plus\Utility\Element $element
   *   The render array Element object.
   */
  public function preRender(Element $element);

}
