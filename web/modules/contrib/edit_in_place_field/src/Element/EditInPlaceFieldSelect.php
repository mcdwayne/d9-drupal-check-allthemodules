<?php

namespace Drupal\edit_in_place_field\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;


/**
 * Provides an example element.
 *
 * @FormElement("edit_in_place_field_select")
 */
class EditInPlaceFieldSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    return Select::processSelect($element, $form_state, $complete_form);
  }


  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return Select::valueCallback($element, $input, $form_state);
  }

  /**
   * Prepares a select render element.
   */
  public static function preRenderSelect($element) {
    $element = Select::preRenderSelect($element);
    static::setAttributes($element, ['edit-in-place']);
    return $element;
  }
}