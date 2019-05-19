<?php

/**
 * @file
 * Contains \Drupal\vcl\Plugin\Swap\.
 */

namespace Drupal\swaps\Plugin\Swap;

use Drupal\swaps\SwapBase;

/**
 * Provides a 'Div' swap.
 *
 * @Swap(
 *   id = "swap_div",
 *   name = "Div",
 *   description = @Translation("Add div which you can add a bootstrap class."),
 *   container = true,
 *   tip = "[div class='row | container'] Content [/div]"
 * )
 */

class SwapDiv extends SwapBase {

  /**
   * Get all attributes of the swap and validate it.
   */
  public function processCallback($attrs, $text) {
    $attrs = $this->setAttrs(array(
      'class' => '',
    ),
      $attrs
    );

    // Validate exists class and extraxclass index
    if(isset($attrs['class']) && isset($attrs['extraclass'])) {
      $attrs['class'] = $this->addClass($attrs['class'], $attrs['extraclass']);
      $attrs['style'] = $this->getStyle($attrs);
    }
    return $this->theme($attrs, $text);
  }

  /**
   * Create the string of the swap.
   */
  public function theme($attrs, $text) {

    // Validate exists id.
    $id = isset($attrs['id']) && ($attrs['id'] != '') ? ' id="' . $attrs['id'] . '"' : "";
    // Validate exists class.
    $class = isset($attrs['class']) && ($attrs['class'] != '') ? $attrs['class'] : "";
    // Validate exists style.
    $style = isset($attrs['style']) && ($attrs['style'] != '') ? $attrs['style'] : "";

    return '<div' . $id . ' class="' . $class . '" ' . $style . ' >' . $text . '</div>';
  }

}
