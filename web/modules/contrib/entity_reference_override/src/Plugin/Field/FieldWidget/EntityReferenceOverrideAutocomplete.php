<?php

namespace Drupal\entity_reference_override\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * @FieldWidget(
 *   id = "entity_reference_override_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field with title override."),
 *   field_types = {
 *     "entity_reference_override"
 *   }
 * )
 */
class EntityReferenceOverrideAutocomplete extends EntityReferenceAutocompleteWidget {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = array(
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
      '#theme_wrappers' => ['container'],
    );
    $widget['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state);
    $widget['override'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->override : '',
      '#maxlength' => 255,
      '#size' => 40,
      '#weight' => 10,
    );

    if ($this->fieldDefinition->getFieldStorageDefinition()->isMultiple()) {
      $widget['override']['#placeholder'] = $this->fieldDefinition->getSetting('override_label');
    }
    else {
      $widget['override']['#title'] = $this->fieldDefinition->getSetting('override_label');
    }

    return $widget;
  }
}
