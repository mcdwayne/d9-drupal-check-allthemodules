<?php

namespace Drupal\entityreference_view_mode\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

trait EntityReferenceViewModeFieldWidgetTrait {

  public function getSelections(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // The name of the field created in the UI.
    $field_name = $items->getName();

    // Access the user's from input values.
    $inputs = $form_state->getUserInput();

    // Access the forms current values.
    $values = $form_state->getValues();

    // Track the triggereing element form ajax calls.
    $trigger = $form_state->getTriggeringElement();


    if (isset($trigger['#type'])) {
      // Element parent array keys.
      $parents = $element['#field_parents'];
      $parents[] = $field_name;
      $parents[] = $delta;
      // Extract the values.
      $selections = NestedArray::getValue($inputs, $parents);
    }
    else {
      $selections = [
        'target_type' => $items[$delta]->target_type,
        'content' => $items[$delta]->content,
        'view_mode' => $items[$delta]->view_mode
      ];
    }

    $selections['bundle'] = '';

    if (!$selections['target_type']) {
      $available_target_types = $this->availableEntityTypes();
      reset($available_target_types);
      $selections['target_type'] = key($available_target_types);
    }

    // Ensure we have a fuly loaded entity
    if ($selections['content']) {
      if (!is_numeric($selections['content'])) {
        $selections['content'] = EntityAutocomplete::extractEntityIdFromAutocompleteInput($selections['content']);
      }
      $entity = entity_load($selections['target_type'], $selections['content']);

      if ($entity) {
        $selections['content'] = $entity;
        $selections['bundle'] =  $entity->bundle();
      } else {
          $selections['content'] = '';
          $selections['bundle'] = '';
      }
    }

    // If we have a target type and a bundle then lets default the view mode to the first one in the list.
    if ($selections['target_type'] &&  $selections['content'] && !$selections['view_mode']) {
      $available_view_modes = $this->availableViewModes($selections['target_type'],  $selections['content']->bundle());
      reset($available_view_modes);
      $selections['view_mode'] = key($available_view_modes);
    }

      return $selections;
  }
}

