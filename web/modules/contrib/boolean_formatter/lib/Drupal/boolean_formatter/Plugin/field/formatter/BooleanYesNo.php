<?php

/**
 * @file
 * Contains \Drupal\boolean_formatter\Plugin\field\formatter\BooleanYesNo.
 */

namespace Drupal\boolean_formatter\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field\FieldInterface;

/**
 * Plugin implementation of the 'boolean_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "boolean_yes_no",
 *   label = @Translation("Yes/No"),
 *   field_types = {
 *     "list_boolean"
 *   },
 *   settings = {
 *     "format" = "yes-no",
 *     "custom_on" = "",
 *     "custom_off" = "",
 *     "reverse" = FALSE
 *   }
 * )
 */
class BooleanYesNo extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $field_name = $this->fieldDefinition->getFieldName();

    $form['format'] = array(
      '#type' => 'select',
      '#title' => t('Output format'),
      '#options' => $this->getFormatOptions(),
      '#default_value' => $this->getSetting('format'),
    );
    $form['custom_on'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom output for On'),
      '#default_value' => $this->getSetting('custom_on'),
      '#states' => array(
        'visible' => array(
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][format]"]' => array('value' => 'custom'),
        ),
      ),
    );
    $form['custom_off'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom output for Off'),
      '#default_value' => $this->getSetting('custom_off'),
      '#states' => array(
        'visible' => array(
          'select[name="fields[' . $field_name . '][settings_edit_form][settings][format]"]' => array('value' => 'custom'),
        ),
      ),
    );
    $form['reverse'] = array(
      '#type' => 'checkbox',
      '#title' => t('Reverse'),
      '#description' => t('If checked, true will be displayed as false.'),
      '#default_value' => $this->getSetting('reverse'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $formats = $this->getFormatOptions($this->settings);
    $summary[] = t('Output format: %format', array('%format' => $formats[$this->getSetting('format')]));
    $summary[] = t('Reversed: @reversed', array('@reversed' => $this->displayValueWithFormat($this->getSetting('reverse'), 'yes-no', array('reverse' => FALSE))));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, FieldInterface $items) {
    $elements = array();

    $values = options_allowed_values($this->fieldDefinition, $entity);
    foreach ($items as $delta => $item) {
      $value = !empty($values[1]) ? $item->value == $values[1] : !empty($item->value);
      $text = $this->displayValueWithFormat($value, $this->getSetting('format'), $this->settings);
      $elements[$delta] = array('#markup' => field_filter_xss($text));
    }

    return $elements;
  }

  public function getFormats(array $options = array()) {
    $formats = array(
      'yes-no' => array(t('Yes'), t('No')),
      'true-false' => array(t('True'), t('False')),
      'on-off' => array(t('On'), t('Off')),
      'enabled-disabled' => array(t('Enabled'), t('Disabled')),
      'boolean' => array(1, 0),
      'unicode-yes-no' => array('✔', '✖'),
      'custom' => array(t('Custom')),
    );
    if (isset($options['custom_on']) && isset($options['custom_off'])) {
      $formats['custom'] = array($options['custom_on'], $options['custom_off']);
    }
    return $formats;
  }

  public function getFormatOptions(array $options = array()) {
    $format_options = array();
    foreach ($this->getFormats($options) as $key => $format) {
      $format_options[$key] = implode('/', $format);
    }
    return $format_options;
  }

  public function displayValueWithFormat($value, $format, array $options = array()) {
    $formats = $this->getFormats($options);
    if (!isset($formats[$format])) {
      // If format is invalid, default to the first available format.
      reset($formats);
      $format = key($formats);
    }
    if (!empty($options['reverse'])) {
      $value = !(bool) $value;
    }
    // The first format value is the 'On' value, the second is the 'Off' format.
    return !empty($value) ? $formats[$format][0] : $formats[$format][1];
  }
}
