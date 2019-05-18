<?php

namespace Drupal\cck_select_other\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin implementation of the 'cck_select_other' widget.
 *
 * @FieldWidget(
 *   id = "cck_select_other",
 *   label = @Translation("Select other list"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   }
 * )
 */
class SelectOtherWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'other_label' => t('Other'),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    if ($this->getSetting('other_label')) {
      $summary[] = t('Other label is @label', array('@label' => $this->getSetting('other_label')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['other_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Other label'),
      '#description' => t('Provide an alternate label for "Other".'),
      '#default_value' => $this->getSetting('other_label'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $defaults = $this->getDefaultValue($items, $element['#delta']);
    $field_item_name = str_replace('_', '-', $this->fieldDefinition->getName() . '-' . $element['#delta'] . '-select-other-list');
    $element_class = 'form-item-' . $field_item_name;

    // Setup select other wrapper.
    $element += array(
      '#attributes' => array(
        'class' => array('form-select-other-wrapper', 'cck-select-other-wrapper'),
      ),
      '#attached' => array(
        'library' => array(
          'cck_select_other/widget',
        ),
        'drupalSettings' => array(
          'CCKSelectOther' => array(
            $this->fieldDefinition->getName() => array(
              $delta => $element_class
            ),
          ),
        ),
      ),
    );

    // Setup select list.
    $element['select_other_list'] = array(
      '#title' => $element['#title'],
      '#description' => $element['#description'],
      '#type' => 'select',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $defaults['select'],
      '#required' => $this->fieldDefinition->isRequired(),
      '#attributes' => array(
        'class' => array('form-text form-select form-select-other-list'),
      ),
    );

    // Setup text input.
    $element['select_other_text_input'] = array(
      '#type' => 'textfield',
      '#title' => t('Provide other option'),
      '#title_display' => 'invisible',
      '#default_value' => $defaults['textfield'],
      '#size' => 60,
      '#attributes' => array(
        'class' => array('form-text form-select-other-text-input'),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  static public function validateElement(array $element, FormStateInterface $form_state) {
    $form_state_values = $form_state->getValues();
    $values = NestedArray::getValue($form_state_values, $element['#parents']);

    if (!$element['select_other_list']['#required'] && $values['select_other_list'] == '_none') {
      // Empty select list option.
      $form_state->setValueForElement($element, array('value' => NULL));
    }
    elseif ($element['select_other_list']['#required'] && $values['select_other_list'] == '') {
      // Empty select list option for required field.
      $form_state
        ->setValueForElement($element, array('value' => ''))
        ->setError($element, t('You must select an option.'));
    }
    elseif ($element['select_other_list']['#required'] && $values['select_other_list'] == 'other' && !$values['select_other_text_input']) {
      // Empty text input for required field.
      $form_state
        ->setValueForElement($element, array('value' => NULL))
        ->setError($element['select_other_text_input'], t('You must provide a value for this option.'));
    }
    elseif ($values['select_other_list'] == 'other' && $values['select_other_text_input']) {
      // Non-empty text input value.
      $form_state->setValueForElement($element, array('value' => $values['select_other_text_input']));
    }
    elseif ($values['select_other_list'] == 'other' && !$values['select_other_text_input']) {
      // Empty text for non-required field.
      $form_state->setValueForELement($element, array('value' => NULL));
    }
    elseif (!isset($element['select_other_list']['#options'][$values['select_other_list']])) {
      // Non-empty select list value is not in #options. Fail validation before
      // Field constraint can get to it as we MUST override that completely
      // because DrupalWTF.
      $form_state->setError($element['select_other_list'], t('The value you selected is not a valid choice.'));
    }
    else {
      // Non-empty select list value.
      $form_state->setValueForElement($element, array('value' => $values['select_other_list']));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = strip_tags($label);
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * Get the default values from the items for the form elements.
   *
   * @param $items
   *   FieldInterface items.
   * @param $delta
   *   The field item to extract.
   * @return array
   *   An associative array containing the default value for the select element
   *   the default value for the textfield element.
   */
  protected function getDefaultValue(FieldItemListInterface $items, $delta = 0) {
    $item = &$items[$delta];
    $option_keys = array();
    $options = $this->getOptions($items->getEntity());

    if (!empty($options)) {
      $option_keys = array_keys($options);
    }

    if (!$item->{$this->column}) {
      $values = array(
        'select' => $this->fieldDefinition->isRequired() ? '' : '_none',
        'textfield' => '',
      );
    }
    elseif (in_array($item->{$this->column}, $option_keys)) {
      $values = array(
        'select' => $item->{$this->column},
        'textfield' => '',
      );
    }
    else {
      $values = array(
        'select' => 'other',
        'textfield' => $item->{$this->column},
      );
    }

    return $values;
  }

  /**
   *
   * {@inheritdoc}
   *
   * Add the Other option to the allowed values to form the select list option
   * array.
   *
   * This method MUST override OptionsWidgetBase because that class is tightly
   * coupled with its options widgets. DrupalWTF.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      // Limit the settable options for the current user account.
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider($this->column, $entity)
        ->getSettableOptions(\Drupal::currentUser());
      $options['other'] = Html::escape($this->getSetting('other_label'));

      // Add an empty option if the widget needs one.
      if ($empty_option = $this->getEmptyOption()) {
        $options = array('_none' => $empty_option) + $options;
      }

      $module_handler = \Drupal::moduleHandler();
      $context = array(
        'fieldDefinition' => $this->fieldDefinition,
        'entity' => $entity,
      );
      $module_handler->alter('options_list', $options, $context);

      array_walk_recursive($options, array($this, 'sanitizeLabel'));

      // Options might be nested ("optgroups"). If the widget does not support
      // nested options, flatten the list.
      if (!$this->supportsGroups()) {
        $options = $this->flattenOptions($options);
      }

      $this->options = $options;
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyOption() {
    if (!$this->required) {
      return t('- None -');
    }

    if (!$this->has_value) {
      return t('- Select a value -');
    }
  }
}
