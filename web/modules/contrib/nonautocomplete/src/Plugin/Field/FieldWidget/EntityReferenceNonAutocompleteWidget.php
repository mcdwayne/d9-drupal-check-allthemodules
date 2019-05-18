<?php

namespace Drupal\nonautocomplete\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_nonautocomplete' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_nonautocomplete",
 *   label = @Translation("NonAutocomplete"),
 *   description = @Translation("A text field without autocomplete."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceNonAutocompleteWidget extends EntityReferenceAutocompleteWidget {
  /**
   * @inheritDoc
   */
  public static function defaultSettings() {
    $defaultSettings = parent::defaultSettings();
    unset($defaultSettings['match_operator']);
    return $defaultSettings;
  }

  /**
   * @inheritDoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    unset($element['match_operator']);
    return $element;
  }

  /**
   * @inheritDoc
   */
  public function settingsSummary() {
    $settingsSummary = parent::settingsSummary();
    unset($settingsSummary[0]); // match_operator
    return $settingsSummary;
  }

  /**
   * @inheritDoc
   */
  public function getSetting($key) {
    if ($key === 'match_operator') {
      return '';
    }
    else {
      return parent::getSetting($key);
    }
  }

  /**
   * @inheritDoc
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $formElement = parent::formElement($items, $delta, $element, $form, $form_state);
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete
    $formElement['target_id']['#type'] = 'entity_nonautocomplete';
    return $formElement;
  }

}
