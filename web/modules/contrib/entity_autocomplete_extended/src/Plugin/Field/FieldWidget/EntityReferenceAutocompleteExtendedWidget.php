<?php

namespace Drupal\entity_autocomplete_extended\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_autocomplete_extended\Element\EntityAutocompleteExtended;

/**
 * Plugin implementation of the 'entity_reference_autocomplete_extended' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_extended",
 *   label = @Translation("Autocomplete Extended"),
 *   description = @Translation("An extended autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceAutocompleteExtendedWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'results_limit' => EntityAutocompleteExtended::DEFAULT_RESULTS_LIMIT,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['results_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of matching results shown'),
      '#default_value' => $this->getSetting('results_limit'),
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Maximum number of matching results shown: @results_limit', [
      '@results_limit' => $this->getSetting('results_limit'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $build = parent::formElement($items, $delta, $element, $form, $form_state);

    $element = &$build['target_id'];
    $element['#type'] = 'entity_autocomplete_extended';
    $element['#results_limit'] = $this->getSetting('results_limit');

    return $build;
  }

}
