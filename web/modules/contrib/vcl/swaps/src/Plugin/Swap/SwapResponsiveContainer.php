<?php

/**
 * @file
 * Contains \Drupal\vcl\Plugin\Swap\.
 */

namespace Drupal\swaps\Plugin\Swap;

use Drupal\swaps\SwapBase;

/**
 * Provides a 'Responsive Container' swap.
 *
 * @Swap(
 *   id = "swap_respcontainer",
 *   name = "Responsive Container",
 *   description = @Translation("Add the Responsive Container."),
 *   container = true,
 *   children = "swap_column",
 *   tip = "[respcontainer class='class' row_class='row_class'] [/respcontainer]"
 * )
 */

class SwapResponsiveContainer extends SwapBase {

  /**
   * Get all attributes of the swap and validate it.
   */
  public function processCallback($attrs, $text) {
    $attrs = $this->setAttrs(array(
      'class' => 'container',
      'rowclass' => 'row',
    ),
      $attrs
    );

    $attrs['class'] = $this->addClass($this->validateKey($attrs,'class')	, 'container');
    $attrs['rowclass'] = $this->addClass($this->validateKey($attrs,'rowclass')	, 'row');
    $attrs['style'] = $this->getStyle($attrs);

    return $this->theme($attrs, $text);
  }

  /**
   * Create the string of the swap.
   */
  public function theme($attrs, $text) {

    // Validate exists id.
    $id = ($this->validateKey($attrs,'id')	 != '') ? ' id="' . $attrs['id'] . '"' : "";

    return '<div' . $id . ' class="' . $attrs['class'] . '" ' . $attrs['style']
    . ' ><div class="' . $attrs['rowclass'] . '">' . $text . '</div></div>';
  }
}
