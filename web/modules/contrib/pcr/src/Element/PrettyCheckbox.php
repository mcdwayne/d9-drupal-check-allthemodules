<?php

namespace Drupal\pcr\Element;

use Drupal\Core\Render\Element\Checkbox;
use Drupal\pcr\UpdateElementPropertyTrait;

/**
 * Provides a form element for a single pretty_checkbox.
 *
 * Properties:
 * - #return_value: The value to return when the checkbox is checked.
 *
 * Usage example:
 * @code
 * $form['copy'] = array(
 *   '#type' => 'pretty_checkbox',
 *   '#title' => $this->t('Send me a copy'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 *
 * @FormElement("pretty_checkbox")
 */
class PrettyCheckbox extends Checkbox {

  use UpdateElementPropertyTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $pretty_checkbox = parent::getInfo();
    $pretty_checkbox = $this->updateElementProperty($pretty_checkbox);
    return $pretty_checkbox;
  }

}
