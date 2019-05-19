<?php

/**
 * @file
 * Contains \Drupal\toggle\Plugin\field\widget\ToggleWidget.
 */

namespace Drupal\toggle\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\options\Plugin\Field\FieldWidget\OptionsWidgetBase;

/**
 * Plugin implementation of the 'options_toggle' widget.
 *
 * @FieldWidget(
 *   id = "options_toggle",
 *   label = @Translation("Toggle widget"),
 *   field_types = {
 *     "list_boolean",
 *     "list_integer",
 *     "list_float",
 *     "list_text"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ToggleWidget extends OptionsWidgetBase {

  /**
   * @return array
   *   An array of skin names, keyed by machine_name.
   *
   * @TODO This is a temporary stop-gap implementation. Not quite sure what's
   *       the best way to implement this yet.
   */
  protected function getSkins() {
    return array(
      'default' => t('Default'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
//      'ajax' => FALSE,
      'skin' => 'default',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    // AJAX.
//    $element['ajax'] = array(
//      '#type' => 'checkbox',
//      '#title' => t('Use AJAX to update this field\'s value as soon as it\'s changed.'),
//      '#default_value' => $this->getSetting('ajax'),
//    );

    // Display mode.
    $element['skin'] = array(
      '#type' => 'select',
      '#title' => t('Skin'),
      '#options' => $this->getSkins(),
      '#default_value' => $this->getSetting('skin'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    // Skin.
    $skin = $this->getSetting('skin');
    $skins = $this->getSkins();
    $skin_label = array_key_exists($skin, $skins)
      ? $skins[$skin]
      : $skin;
    $summary[] = t('Skin: @skin', array('@skin' => $skin_label));

    // AJAX.
//    $ajax = $this->getSetting('ajax');
//    $summary[] = t('Use AJAX: @ajax', array('@ajax' => ($ajax ? t('Yes') : t('No'))));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, array &$form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items[$delta]);
    $selected = $this->getSelectedOptions($items);
    $type = $this->fieldDefinition->getType();

    // Single checkbox.
    if ($type === 'list_boolean' && !$this->multiple) {
      $element['#title'] = $this->fieldDefinition->getLabel();
      $element['#default_value'] = !empty($selected[0]);
      $element['#type'] = 'toggle_checkbox';
    }
    // Checkboxes/radio buttons.
    else {
      // If required and there is one single option, preselect it.
      if ($this->required && count($options) == 1) {
        reset($options);
        $selected = array(key($options));
      }

      if ($this->multiple) {
        $element += array(
          '#type' => 'toggle_checkboxes',
          '#default_value' => $selected,
          '#options' => $options,
        );
      }
      else {
        $element += array(
          '#type' => 'toggle_radios',
          // Radio buttons need a scalar value. Take the first default value, or
          // default to NULL so that the form element is properly recognized as
          // not having a default value.
          '#default_value' => $selected ? reset($selected) : NULL,
          '#options' => $options,
        );
      }
    }

    // @TODO #ajax
    // @TODO get/add entity id + other metadata for AJAX?

    return $element;
  }

}
