<?php

namespace Drupal\entity_switcher\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'switcher_reference_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "switcher_reference_autocomplete_widget",
 *   label = @Translation("Switcher reference autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "switcher_reference"
 *   }
 * )
 */
class SwitcherReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'match_operator_data_off' => 'CONTAINS',
      'size_data_off' => '60',
      'placeholder_data_off' => '',
      'match_operator_data_on' => 'CONTAINS',
      'size_data_on' => '60',
      'placeholder_data_on' => '',
      'match_operator_switcher' => 'CONTAINS',
      'size_switcher' => '60',
      'placeholder_switcher' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $element['match_operator_' . $item] = [
        '#type' => 'radios',
        '#title' => t('Autocomplete matching'),
        '#default_value' => $this->getSetting('match_operator_' . $item),
        '#options' => $this->getMatchOperatorOptions(),
        '#description' => t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
      ];
      $element['size_' . $item] = [
        '#type' => 'number',
        '#title' => t('Size of textfield'),
        '#default_value' => $this->getSetting('size_' . $item),
        '#min' => 1,
        '#required' => TRUE,
      ];
      $element['placeholder_' . $item] = [
        '#type' => 'textfield',
        '#title' => t('Placeholder'),
        '#default_value' => $this->getSetting('placeholder_' . $item),
        '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $operators = $this->getMatchOperatorOptions();
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      $summary[] = t('Autocomplete matching for @item: @match_operator', ['@item' => $item, '@match_operator' => $operators[$this->getSetting('match_operator_' . $item)]]);
      $summary[] = t('Textfield size for @item: @size', ['@item' => $item, '@size' => $this->getSetting('size_' . $item)]);
      $placeholder = $this->getSetting('placeholder_' . $item);
      if (!empty($placeholder)) {
        $summary[] = t('Placeholder for @item: @placeholder', ['@item' => $item, '@placeholder' => $placeholder]);
      }
      else {
        $summary[] = t('No placeholder for @item', ['@item' => $item]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();

    $elements = [];
    foreach (['data_off', 'data_on', 'switcher'] as $item) {
      // Append the match operation to the selection settings.
      $selection_settings = $this->getFieldSetting('handler_settings_' . $item) + ['match_operator' => $this->getSetting('match_operator_' . $item)];

      $elements[$item . '_id'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => $this->getFieldSetting('target_type_' . $item),
        '#selection_handler' => $this->getFieldSetting('handler_' . $item),
        '#selection_settings' => $selection_settings,
        // Entity reference field items are handling validation themselves via
        // the 'ValidSwitcherReference' constraint.
        '#validate_reference' => FALSE,
        '#maxlength' => 1024,
        '#default_value' => isset($referenced_entities[$delta][$item]) ? $referenced_entities[$delta][$item] : NULL,
        '#size' => $this->getSetting('size_' . $item),
        '#placeholder' => $this->getSetting('placeholder_' . $item),
       ] + $element;
    }

    // Overwrite titles.
    $elements['data_off_id']['#title'] = $this->t('Entity for @value value', ['@value' => $this->t('off')]);
    $elements['data_on_id']['#title'] = $this->t('Entity for @value value', ['@value' => $this->t('on')]);
    $elements['switcher_id']['#title'] = $this->t('Switcher settings');

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values;
  }

}
