<?php

namespace Drupal\nonautocomplete\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an entity non-autocomplete form element.
 *
 * @FormElement("entity_nonautocomplete")
 */
class EntityNonAutocomplete extends EntityAutocomplete {
  /**
   * @inheritDoc
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $processedElement = parent::processEntityAutocomplete($element, $form_state, $complete_form);
    unset($processedElement['#autocomplete_route_name']);
    unset($processedElement['#autocomplete_route_parameters']);
    return $processedElement;
  }

}
