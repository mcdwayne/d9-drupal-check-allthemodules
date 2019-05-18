<?php

namespace Drupal\plus\Plugin\Theme\Template;

use Drupal\plus\Utility\Element;
use Drupal\plus\Utility\Variables;

/**
 * Defines the interface for an object oriented preprocess plugin.
 *
 * @ingroup plugins_preprocess
 */
interface PreprocessInterface {

  /**
   * Preprocess theme hook variables.
   *
   * @param \Drupal\plus\Utility\Variables $variables
   *   The Variables object.
   * @param string $hook
   *   The name of the theme hook.
   * @param array $info
   *   The theme hook info array.
   * @param \Drupal\plus\Utility\Element $element
   *   The Element object, if present.
   */
  public function preprocess(Variables $variables, $hook, array $info, Element $element = NULL);

}
