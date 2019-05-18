<?php

namespace Drupal\reference_value_pair\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "reference_value_select",
 *   label = @Translation("Select list"),
 *   field_types = {
 *     "reference_value_pair",
 *   }
 * )
 */
class ReferenceValueSelectWidget extends OptionsWidgetBase {
  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $property_names = $this->fieldDefinition->getFieldStorageDefinition()->getPropertyNames();
    $this->column = in_array('target_id', $property_names) ? 'target_id' : $this->column;
  }
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size_value' => 60,
      'placeholder_value' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size_value'] = array(
      '#type' => 'number',
      '#title' => $this->t('Size of the value textfield'),
      '#default_value' => $this->getSetting('size_value'),
      '#min' => 1,
      '#required' => TRUE,
    );
    $elements['placeholder_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value placeholder'),
      '#default_value' => $this->getSetting('placeholder_value'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder = $this->getSetting('placeholder_value');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder Value: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = $this->t('No Placeholder Value');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $original_element = $element;
    $elements = [];
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    $flat_options = OptGroup::flattenOptions($options);
    $value = $items->get($delta)->{$this->column};
    $value = isset($flat_options[$value]) ? $value : NULL;

    $element += array(
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $value,
      // Do not display a 'multiple' select box if there is only one option.
      '#multiple' => FALSE, //$this->multiple && count($this->options) > 1,
    );

    $elements['target_id'] = $element;
    $elements['value'] = $original_element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size_value'),
      '#placeholder' => $this->getSetting('placeholder_value'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#multiple' => FALSE,
      );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if ($this->multiple) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->required) {
        return t('- None -');
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->required) {
        return t('- None -');
      }
      if (!$this->has_value) {
        return t('- Select a value -');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
//      if (isset($value['target_id'][0]['value'])) {
//        unset($values[$key]['target_id']);
//        $values[$key]['target_id'] = $value['target_id'][0]['value'];
//      }
      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (isset($value['target_id'][0]) && is_array($value['target_id'][0])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'][0];
      }
      if (is_array($value['target_id']) && empty($value['target_id'])) {
        $values[$key]['target_id'] = NULL;
      }
    }

    return $values;
  }

}
