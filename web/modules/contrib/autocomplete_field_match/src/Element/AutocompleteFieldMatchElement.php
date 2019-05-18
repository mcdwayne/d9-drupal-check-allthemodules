<?php

namespace Drupal\autocomplete_field_match\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Site\Settings;
use Drupal\Component\Utility\Xss;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an
 * array of entity objects.
 *
 * @FormElement("autocomplete_field_match")
 */
class AutocompleteFieldMatchElement extends EntityAutocomplete {

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
    // Nothing to do if there is no target entity type.
    if (empty($element['#target_type'])) {
      throw new \InvalidArgumentException('Missing required #target_type parameter.');
    }

    // Provide default values and sanity checks for the #autocreate parameter.
    if ($element['#autocreate']) {
      if (!isset($element['#autocreate']['bundle'])) {
        throw new \InvalidArgumentException("Missing required #autocreate['bundle'] parameter.");
      }
      // Default the autocreate user ID to the current user.
      $element['#autocreate']['uid'] = isset($element['#autocreate']['uid']) ? $element['#autocreate']['uid'] : \Drupal::currentUser()->id();
    }

    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = isset($element['#selection_settings']) ? $element['#selection_settings'] : [];
    $data = serialize($selection_settings) . $element['#target_type'] . $element['#selection_handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('autocomplete_field_match');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $element['#autocomplete_route_name'] = 'autocomplete_field_match.autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'target_type' => $element['#target_type'],
      'selection_handler' => $element['#selection_handler'],
      'selection_settings_key' => $selection_settings_key,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  private static function fieldMatchQuery($entity_type, $field_to_check, $input, $where, $langcodes, $conjunction) {
    $langcode = NULL;
    if (isset($langcodes[0])) {
      $langcode = array_shift($langcodes);
    }
    // TODO? - Inject entity.query service?
    // There are many other \Drupal calls in this based on
    // Drupal\Core\Entity\Element\EntityAutocomplete
    // So not sure if useful or necessary.
    $query = \Drupal::entityQuery($entity_type)
      ->condition($field_to_check . '.value', $input, $where, $langcode);
    if (!empty($langcodes)) {
      if ($conjunction == 'or') {
        $and_or = $query->orConditionGroup();
      }
      elseif ($conjunction == 'and') {
        $and_or = $query->andConditionGroup();
      }
      foreach ($langcodes as $another_langcode) {
        $and_or->condition($field_to_check[0][1] . '.value', $input, $where, $another_langcode);
      }
      $query->condition($and_or);
    }

    return array_values($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  private static function combineMatches($field_to_check, $autocomplete_field_matches, $input, $where, $langcodes, $conjunction) {
    $autocomplete_field_match = [];
    foreach ($field_to_check as $index => $field) {
      // If this method is called, first field has already been checked.
      if ($index < 1) {
        continue;
      }
      $autocomplete_field_matches[] = self::fieldMatchQuery($field_to_check[$index][0], $field_to_check[$index][1], $input, $where, $langcodes, $conjunction);
    }
    if ($conjunction == 'or') {
      foreach ($autocomplete_field_matches as $matches) {
        $autocomplete_field_match = array_merge($autocomplete_field_match, $matches);
      }
    }
    elseif ($conjunction == 'and') {
      $partial = FALSE;
      $num_of_partial = 0;
      // Pull the first match off and set so we dont intersect with empty array.
      $first_match = array_shift($autocomplete_field_matches);
      if (!empty($first_match)) {
        $partial = TRUE;
        $num_of_partial = count($first_match);
        $autocomplete_field_match = $first_match;
      }
      // Now make sure match is in all fields.
      foreach ($autocomplete_field_matches as $matches) {
        if (!empty($matches)) {
          $partial = TRUE;
          $num_of_partial = $num_of_partial + count($matches);
        }
        $autocomplete_field_match = array_intersect($autocomplete_field_match, $matches);
      }
      // Rekey the array, make sure values are unique and send error if needed.
      $autocomplete_field_match = array_unique(array_values($autocomplete_field_match));
      if (empty($autocomplete_field_match) && $partial) {
        return ['num_of_partial' => $num_of_partial];
      }
    }

    return $autocomplete_field_match;
  }

  /**
   * {@inheritdoc}
   */
  private static function getAutocompleteFieldMatch(array $element, FormStateInterface $form_state, $input) {
    $autocomplete_field_matches = [];
    $autocomplete_field_match = [];
    if ($element['#type'] == 'autocomplete_field_match' && !empty($element['#selection_settings']['autocomplete_field_match'])) {
      $fields_to_check = $element['#selection_settings']['autocomplete_field_match'];
      $conjunction = $element['#selection_settings']['afm_operator_and_or'];
      $where = $element['#selection_settings']['afm_operator_where'];
      $langcodes = $element['#selection_settings']['afm_operator_langcode'];

      // Verify our form autocomplete_field_matches values
      // get the entity based on autocomplete_field_matches.
      $field_to_check = [];
      foreach ($fields_to_check as $field) {
        $field_to_check[] = explode('.', $field);
      }
      $autocomplete_field_matches[] = self::fieldMatchQuery($field_to_check[0][0], $field_to_check[0][1], $input, $where, $langcodes, $conjunction);

      if (count($field_to_check) > 1) {
        $autocomplete_field_match = self::combineMatches($field_to_check, $autocomplete_field_matches, $input, $where, $langcodes, $conjunction);
        if (isset($autocomplete_field_match['num_of_partial'])) {
          $params = [
            '%value' => $input,
            '%count' => $autocomplete_field_match['num_of_partial'],
          ];
          // Error if there are more than 1 matching entities.
          $form_state->setError($element, t('%count entities contain %value, but not in all fields selected: "%fields". Specify the one you want selecting it from the dropdown autocomplete feature.', ['%fields' => implode('", "', $fields_to_check)] + $params));
        }

      }
      else {
        $autocomplete_field_match = $autocomplete_field_matches[0];
      }
    }

    return array_unique($autocomplete_field_match);
  }

  /**
   * Form element validation handler for entity_autocomplete elements.
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    if (!empty($element['#value'])) {
      $options = $element['#selection_settings'] + [
        'target_type' => $element['#target_type'],
        'handler' => $element['#selection_handler'],
      ];
      // Core does this in Drupal\Core\Entity\Element\EntityAutocomplete
      // but phpcs --standard=Drupal  throws error:
      // "Inline doc block comments are not allowed" so commenting out.
      // @var /Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler
      // TODO? implement ContainerFactoryPluginInterface & inject all services?
      $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
      $autocreate = (bool) $element['#autocreate'] && $handler instanceof SelectionWithAutocreateInterface;

      // GET forms might pass the validated data around on the next request, in
      // which case it will already be in the expected format.
      if (is_array($element['#value'])) {
        $value = $element['#value'];
      }
      else {
        $input_values = $element['#tags'] ? Tags::explode($element['#value']) : [$element['#value']];

        foreach ($input_values as $input) {
          $match = static::extractEntityIdFromAutocompleteInput($input);
          if ($match === NULL) {
            // Try to get a match from the input string when the user didn't use
            // the autocomplete but filled in a value manually.
            $input = Xss::filter($input);
            $autocomplete_field_match = self::getAutocompleteFieldMatch($element, $form_state, $input);

            if (count($autocomplete_field_match) == 1 && !empty($autocomplete_field_match[0])) {
              $match = $autocomplete_field_match[0];
            }
            else {
              if (count($autocomplete_field_match) > 1 && !empty($autocomplete_field_match[0])) {
                $params = [
                  '%value' => $input,
                  '%count' => count($autocomplete_field_match),
                ];
                // Error if there are more than 1 matching entities.
                $fields_to_check = $element['#selection_settings']['autocomplete_field_match'];
                $form_state->setError($element, t('%count entities contain %value in fields "%fields". Specify the one you want selecting it from the dropdown autocomplete feature.', ['%fields' => implode('", "', $fields_to_check)] + $params));
              }
              $match = static::matchEntityByTitle($handler, $input, $element, $form_state, !$autocreate);
            }
          }

          if ($match !== NULL) {
            // We need to validate that the entity found can be referenced.
            $element['#validate_reference'] = TRUE;

            $value[] = [
              'target_id' => $match,
            ];
          }
          elseif ($autocreate) {
            /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $handler */
            // Auto-create item. See an example of how this is handled in
            // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
            $value[] = [
              'entity' => $handler->createNewEntity($element['#target_type'], $element['#autocreate']['bundle'], $input, $element['#autocreate']['uid']),
            ];
          }
        }
      }

      // Check that the referenced entities are valid, if needed.
      if ($element['#validate_reference'] && !empty($value)) {
        // Validate existing entities.
        $ids = array_reduce($value, function ($return, $item) {
          if (isset($item['target_id'])) {
            $return[] = $item['target_id'];
          }
          return $return;
        });

        if ($ids) {
          $valid_ids = $handler->validateReferenceableEntities($ids);
          if ($invalid_ids = array_diff($ids, $valid_ids)) {
            foreach ($invalid_ids as $invalid_id) {
              $params = [
                '%input' => $input,
                '%type' => $element['#target_type'],
                '%id' => $invalid_id,
              ];
              $form_state->setError($element, t('The entity found for %input (%type: %id) cannot be referenced.', $params));
            }
          }
        }

        // Validate newly created entities.
        $new_entities = array_reduce($value, function ($return, $item) {
          if (isset($item['entity'])) {
            $return[] = $item['entity'];
          }
          return $return;
        });

        if ($new_entities) {
          if ($autocreate) {
            $valid_new_entities = $handler->validateReferenceableNewEntities($new_entities);
            $invalid_new_entities = array_diff_key($new_entities, $valid_new_entities);
          }
          else {
            // If the selection handler does not support referencing newly
            // created entities, all of them should be invalidated.
            $invalid_new_entities = $new_entities;
          }

          foreach ($invalid_new_entities as $entity) {
            /** @var \Drupal\Core\Entity\EntityInterface $entity */
            $form_state->setError($element, t('This entity (%type: %label) cannot be referenced.', ['%type' => $element['#target_type'], '%label' => $entity->label()]));
          }
        }
      }

      // Use only the last value if the form element does not support multiple
      // matches (tags).
      if (!$element['#tags'] && !empty($value)) {
        $last_value = $value[count($value) - 1];
        $value = isset($last_value['target_id']) ? $last_value['target_id'] : $last_value;
      }
    }

    $form_state->setValueForElement($element, $value);
  }

}
