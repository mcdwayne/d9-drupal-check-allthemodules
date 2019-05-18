<?php

namespace Drupal\pcr\Element;

use Drupal\Core\Render\Element\Radio;
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
 *   '#type' => 'pretty_radio',
 *   '#title' => $this->t('Send me a copy'),
 * );
 * @endcode
 *
 * @see \Drupal\Core\Render\Element\Checkboxes
 *
 * @FormElement("pretty_radio")
 */
class PrettyRadio extends Radio {

  use UpdateElementPropertyTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $pretty_radio = parent::getInfo();
    $pretty_radio = $this->updateElementProperty($pretty_radio);
    return $pretty_radio;
  }

}
