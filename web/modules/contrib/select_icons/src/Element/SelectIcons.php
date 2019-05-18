<?php

namespace Drupal\select_icons\Element;

use Drupal\Core\Render\Element\Select;

/**
 * Provides a form element for a drop-down menu extended by jQuery UI.
 *
 * Properties:
 * - #options: An associative array, where the keys are the returned values for
 *   each option, and the values are the options to be shown in the drop-down
 *   list.
 * - #options_attributes: An associative array, where the keys are the options
 *   values and the values are attributes (objects of type Attribute).
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 *
 * @FormElement("select_icons")
 */
class SelectIcons extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    // We need to override theme function.
    $info['#theme'] = 'select_icons';

    // We also provide custom attribute to allow per option attributes.
    $info['#options_attributes'] = [];

    return $info;
  }
}
