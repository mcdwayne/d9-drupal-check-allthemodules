<?php

namespace Drupal\errw\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;

/**
 * Just like EntityAutocomplete but uses Errw route.
 *
 * @FormElement("errw")
 */
class Errw extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element = parent::processEntityAutocomplete($element, $form_state, $complete_form);
    $element['#autocomplete_route_name'] = 'errw.errw';
    return $element;
  }

}
