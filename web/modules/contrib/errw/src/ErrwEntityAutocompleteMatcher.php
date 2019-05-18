<?php

namespace Drupal\errw;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

/**
 * Works just as core, but getMatches() also renders other fields specified in
 * widget's settings.
 */
class ErrwEntityAutocompleteMatcher extends EntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   *
   * Works just as core, but getMatches() also renders other fields specified in
   * widget's settings.
   *
   * @see \Drupal\Core\Entity\EntityAutocompleteMatcher
   *
   * @param string $target_type
   *   The ID of the target entity type.
   * @param string $selection_handler
   *   The plugin ID of the entity reference selection handler.
   * @param array $sett
   *   An array of settings that will be passed to the selection handler.
   * @param string $string
   *   (optional) The label of the entity to query by.
   *
   * @return array
   *   An array of matched entity labels, in the format required by the AJAX
   *   autocomplete API (e.g. array('value' => $value, 'label' => $label)).
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specified entity.
   *
   * @see \Drupal\system\Controller\EntityAutocompleteController
   */
  public function getMatches($target_type, $selection_handler, $sett, $string = '') {
    if (empty(self::has_set($sett, 'fields', ''))) {
      return parent::getMatches($target_type, $selection_handler, $sett, $string);
    }

    $matches = [];

    $options = $sett + [
        'target_type' => $target_type,
        'handler' => $selection_handler,
      ];
    $handler = $this->selectionManager->getInstance($options);

    if (!isset($string)) {
      return $matches;
    }

    // Get an array of matching entities.
    $match_operator = !empty($sett['match_operator']) ? $sett['match_operator'] : 'CONTAINS';
    $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

    // ----------------------------------------------------------------- Errw:

    $storage = \Drupal::entityTypeManager()->getStorage($target_type);

    // Which fields should be rendered in autocomplete result other than
    // title field.
    $render_template = self::has_set($sett, 'template', '[{}]');
    $fields_values_glue = self::has_set($sett, 'glue', ', ');
    $prepend_entity_label = self::has_set($sett, 'prepend_entity_label', TRUE);
    $field_label_template = self::has_set($sett, 'field_label_template', '[]: {}');
    $title_for_all = self::has_set($sett, 'all_titled', TRUE);

    $fls = trim(self::has_set($sett, 'titled_fields', ''));
    $fields_with_title = [];
    foreach (explode(',', str_replace(' ', '', $fls)) as $titled_field) {
      $fields_with_title[] = trim($titled_field);
    }

    $fields_to_render = [];
    $fls = trim(self::has_set($sett, 'fields', ''));
    foreach (explode(',', str_replace(' ', '', $fls)) as $fr) {
      $fields_to_render[] = trim($fr);
    }

    // Loop through the entities and convert them into autocomplete output.
    foreach ($entity_labels as $values) {
      foreach ($values as $entity_id => $label) {
        $rendered = ' ';
        if (!empty($fields_to_render)) {
          $load = $storage->load($entity_id);
          $field_values = [];
          foreach ($fields_to_render as $field_to_render) {
            $titled = $title_for_all || in_array($field_to_render, $fields_with_title);
            $field_value = isset($load->{$field_to_render}) ? $load->{$field_to_render}->value : '?';
            if ($titled) {
              $f = str_replace('{}', $field_value, $field_label_template);
              // todo field title
              $f = str_replace('[]', $field_to_render, $f);
              $field_values[] = $f;
            }
            else {
              $field_values[] = $field_value;
            }
          }
          $field_values = implode($fields_values_glue, $field_values);
          $rendered = ' ' . str_replace('{}', $field_values, $render_template) . ' ';
        }
        $key = $prepend_entity_label
          ? "$label$rendered($entity_id)"
          : "$rendered($entity_id)";

        // Strip things like starting/trailing white spaces, line breaks and
        // tags.
        $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
        // Names containing commas or quotes must be wrapped in quotes.
        $key = Tags::encode($key);
        $matches[] = ['value' => $key, 'label' => $key];
      }
    }

    return $matches;
  }

  private static function has_set(array $s, $name, $def) {
    $name = 'errw_' . $name;
    return isset($s[$name]) && !empty(isset($s[$name])) ? $s[$name] : $def;
  }

}
