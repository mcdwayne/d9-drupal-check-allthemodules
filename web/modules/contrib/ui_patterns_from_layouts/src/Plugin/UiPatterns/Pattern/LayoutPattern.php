<?php

namespace Drupal\ui_patterns_from_layouts\Plugin\UiPatterns\Pattern;

use Drupal\ui_patterns\Plugin\PatternBase;

/**
 * The UI Pattern plugin.
 *
 * @UiPattern(
 *   id = "from_layouts",
 *   label = @Translation("Layout Pattern"),
 *   description = @Translation("Pattern based on a Drupal layout definition."),
 *   deriver = "\Drupal\ui_patterns_from_layouts\Plugin\Deriver\LayoutDeriver"
 * )
 */
class LayoutPattern extends PatternBase {

}
