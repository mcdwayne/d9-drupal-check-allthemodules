<?php

namespace Drupal\squid_autocomplete\Element;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("squid_entity_autocomplete")
 */
class SquidEntityAutocomplete extends EntityAutocomplete {

  /**
   * Adds entity autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #target_type: The ID of the target entity type.
   *   - #selection_handler: The plugin ID of the entity reference selection
   *     handler.
   *   - #selection_settings: An array of settings that will be passed to the
   *     selection handler.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the #target_type or #autocreate['bundle'] are
   *   missing.
   */
  public static function processEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    parent::processEntityAutocomplete($element, $form_state, $complete_form);
    // Change the route to our Squid matching route.
    $element['#autocomplete_route_name'] = 'squid_autocomplete.entity_autocomplete';
    return $element;
  }

  /**
   * Finds an entity from an autocomplete input without an explicit ID.
   *
   * The method will return an entity ID if one single entity unambuguously
   * matches the incoming input, and sill assign form errors otherwise.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler
   *   Entity reference selection plugin.
   * @param string $input
   *   Single string from autocomplete element.
   * @param array $element
   *   The form element to set a form error.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param bool $strict
   *   Whether to trigger a form error if an element from $input (eg. an entity)
   *   is not found.
   *
   * @return int|null
   *   Value of a matching entity ID, or NULL if none.
   */
  protected static function matchEntityByTitle(SelectionInterface $handler, $input, array &$element, FormStateInterface $form_state, $strict) {
    $entities_by_bundle = $handler->getReferenceableEntities($input, '=', 6);
    $entities = array_reduce($entities_by_bundle, function ($flattened, $bundle_entities) {
      return $flattened + $bundle_entities;
    }, []);
    $params = array(
      '%value' => $input,
      '@value' => $input,
    );
    if (empty($entities)) {
      if ($strict) {
        // Error if there are no entities available for a required field.
        $form_state->setError($element, t('There are no entities matching "%value".', $params));
      }
    }
    elseif (count($entities) > 5) {
      $params['@id'] = key($entities);
      // Error if there are more than 5 matching entities.
      $form_state->setError($element, t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value [id:@id]".', $params));
    }
    elseif (count($entities) > 1) {
      // More helpful error if there are only a few matching entities.
      $multiples = array();
      foreach ($entities as $id => $name) {
        $multiples[] = $name . ' [id:' . $id . ']';
      }
      $params['@id'] = $id;
      $form_state->setError($element, t('Multiple entities match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value [id:@id]".', array('%multiple' => implode('", "', $multiples))));
    }
    else {
      // Take the one and only matching entity.
      return key($entities);
    }
  }

  /**
   * Converts an array of entity objects into a string of entity labels.
   *
   * This method is also responsible for checking the 'view label' access on the
   * passed-in entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects.
   *
   * @return string
   *   A string of entity labels separated by commas.
   */
  public static function getEntityLabels(array $entities) {
    $entity_labels = array();
    foreach ($entities as $entity) {
      // Use the special view label, since some entities allow the label to be
      // viewed, even if the entity is not allowed to be viewed.
      $label = ($entity->access('view label')) ? $entity->label() : t('- Restricted access -');

      // Take into account "autocreated" entities.
      if (!$entity->isNew()) {
        $label .= ' [id:' . $entity->id() . ']';
      }

      // Labels containing commas or quotes must be wrapped in quotes.
      $entity_labels[] = Tags::encode($label);
    }

    return implode(', ', $entity_labels);
  }

  /**
   * Extracts the entity ID from the autocompletion result.
   *
   * @param string $input
   *   The input coming from the autocompletion result.
   *
   * @return mixed|null
   *   An entity ID or NULL if the input does not contain one.
   */
  public static function extractEntityIdFromAutocompleteInput($input) {
    $match = NULL;

    // Take "label (entity id)', match the ID from parenthesis when it's a
    // number.
    if (preg_match("/.+\s\[id:(\d+)\]/", $input, $matches)) {
      $match = $matches[1];
    }
    // Match the ID when it's a string (e.g. for config entity types).
    elseif (preg_match("/.+\s\[id:([\w.]+)\]/", $input, $matches)) {
      $match = $matches[1];
    }

    return $match;
  }

}
