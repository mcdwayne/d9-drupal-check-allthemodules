<?php

namespace Drupal\linky\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides an entity autocomplete form element just for linky.
 *
 * @FormElement("linky_entity_autocomplete")
 */
class LinkyEntityAutocomplete extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    $info['#element_validate'] = [[$class, 'validateEntityAutocomplete']];
    $info['#allow_duplicate_urls'] = TRUE;
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($element['#target_type'] !== 'linky') {
      parent::validateEntityAutocomplete($element, $form_state, $complete_form);
    }
    else {
      // We build a new link entity using the provided URL and linky title.
      $value = NULL;
      if (!empty($element['#value'])) {
        $options = [
          'target_type' => $element['#target_type'],
          'handler' => $element['#selection_handler'],
          'handler_settings' => $element['#selection_settings'],
        ];
        /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
        $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance($options);
        $autocreate = (bool) $element['#autocreate'] && $handler instanceof SelectionWithAutocreateInterface;
        $allow_duplicate_urls = (bool) $element['#allow_duplicate_urls'] && $handler instanceof SelectionWithAutocreateInterface;

        // GET forms might pass the validated data around on the next request,
        // in which case it will already be in the expected format.
        if (is_array($element['#value'])) {
          $value = $element['#value'];
        }
        else {
          $input_values = [$element['#value']];

          foreach ($input_values as $input) {
            $match = static::extractEntityIdFromAutocompleteInput($input);
            // Only look for matches on the URI input if we aren't allowing
            // duplicate urls.
            if (!$allow_duplicate_urls && $match === NULL) {
              // Try to get a match from the input string when the user didn't
              // use the autocomplete but filled in a value manually.
              $match = static::matchEntityByTitle($handler, $input, $element, $form_state, !$autocreate);
            }

            if ($match !== NULL) {
              $value[] = [
                'target_id' => $match,
              ];
            }
            elseif ($autocreate) {
              /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface $handler */
              // Auto-create item. See an example of how this is handled in
              // \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::presave().
              $input = [
                'uri' => $input,
              ];

              $element_parents = $element['#parents'];
              // Remove the field name.
              array_pop($element_parents);
              $values = $form_state->getValue($element_parents);
              $input['title'] = $values['linky']['linky_title'];
              if (empty($input['title'])) {
                $form_state->setErrorByName(implode('][', array_merge($element_parents, ['linky', 'linky_title'])), t('You must provide a title.'));
                // Remove the invisible class from this element.
                $array_parents = $element['#array_parents'];
                array_pop($array_parents);
                $class_parents = array_merge($array_parents, [
                  'linky',
                  '#attributes',
                  'class',
                ]);
                $classes = NestedArray::getValue($complete_form, $class_parents);
                if ($key = array_search('invisible', $classes)) {
                  unset($classes[$key]);
                  NestedArray::setValue($complete_form, $class_parents, $classes);
                }
              }
              // Validate URL.
              $valid_url = TRUE;
              $url = FALSE;
              try {
                $url = Url::fromUri($input['uri']);
              }
              // If the URL is malformed this constraint cannot check further.
              catch (\InvalidArgumentException $e) {
                $valid_url = FALSE;
              }
              if ($url) {
                // Disallow external URLs using untrusted protocols.
                if (!$url->isExternal() || !in_array(parse_url($url->getUri(), PHP_URL_SCHEME), UrlHelper::getAllowedProtocols())) {
                  $valid_url = FALSE;
                }
              }
              if (!$valid_url) {
                $form_state->setError($element, t('You have entered an invalid URL. Please enter an external URL.'));
              }
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
                $form_state->setError($element, t('The referenced entity (%type: %id) does not exist.', ['%type' => $element['#target_type'], '%id' => $invalid_id]));
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

}
