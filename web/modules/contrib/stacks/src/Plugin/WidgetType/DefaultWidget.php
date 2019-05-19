<?php

namespace Drupal\stacks\Plugin\WidgetType;

use Drupal\stacks\Plugin\WidgetTypeBase;

/**
 * DefaultWidget.
 *
 * @WidgetType(
 *   id = "default_widget",
 *   label = @Translation("Default Widget"),
 * )
 */
class DefaultWidget extends WidgetTypeBase {

  /**
   * Modify the render array before output.
   */
  public function modifyRenderArray(&$render_array, $options = []) {
    return $render_array;
  }

  /**
   * Define the fields that should not be sent to the template as variables.
   * These are usually fields on the bundle that you want to handle via
   * programming only, as options in the code.
   */
  public function fieldExceptions() {
    return [];
  }

}

