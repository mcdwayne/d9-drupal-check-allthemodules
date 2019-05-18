<?php

namespace Drupal\entity_autocomplete_extended\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an extended entity autocomplete form element.
 *
 * Includes #results_limit property that sets the maximum number of results
 * shown in autocomplete, on top of entity_autocomplete functionality and
 * properties.
 *
 * @FormElement("entity_autocomplete_extended")
 */
class EntityAutocompleteExtended extends EntityAutocomplete {

  /**
   * Default value for maximum number of matching results shown.
   *
   * @var int
   */
  const DEFAULT_RESULTS_LIMIT = 10;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#results_limit'] = static::DEFAULT_RESULTS_LIMIT;
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Copy results limit value into the selection settings that will be picked
    // up by the entity autocomplete matcher service.
    // @see \Drupal\entity_autocomplete_extended\Entity\EntityAutocompleteExtendedMatcher::getMatches.
    if (1 > (int) $element['#results_limit']) {
      \Drupal::logger('entity_autocomplete_extended')
        ->warning('Autocomplete property #results_limit set at invalid value 0 or lower. Using default limit of @default.',
          [
            '@default' => static::DEFAULT_RESULTS_LIMIT,
          ]);
      $element['#results_limit'] = static::DEFAULT_RESULTS_LIMIT;
    }
    $element['#selection_settings']['entity_autocomplete_extended_results_limit'] = $element['#results_limit'];
    return parent::processEntityAutocomplete($element, $form_state, $complete_form);
  }

}
