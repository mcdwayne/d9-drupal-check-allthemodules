<?php

/**
 * @file
 * Contains \Drupal\select_or_other\Plugin\Field\FieldWidget\Widget.
 */

namespace Drupal\select_or_other\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'select_or_other' widget.
 *
 * @FieldWidget(
 *   id = "select_or_other",
 *   label = @Translation("Select or other"),
 *   field_types = {
 *     "string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class Widget extends SelectOrOtherWidgetBase {

  /**
   * @var string
   */
  protected $options;

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['available_options'] = array(
      '#type' => 'textarea',
      '#title' => t('Available options'),
      '#description' => t('A list of values that are, by default, available for selection. Enter one value per line, in the format key|label. The key is the value that will be stored in the database, and the label is what will be displayed to the user.'),
      '#default_value' => $this->getSetting('available_options'),
      '#required' => TRUE,
    );
    $form['other'] = array(
      '#type' => 'textfield',
      '#title' => t('<em>Other</em> option'),
      '#description' => t('Label for the option that the user will choose when they want to supply an <em>other</em> value.'),
      '#default_value' => $this->getSetting('other'),
      '#required' => TRUE,
    );
    $form['other_title'] = array(
      '#type' => 'textfield',
      '#title' => t('<em>Other</em> field title'),
      '#description' => t('Label for the field in which the user will supply an <em>other</em> value.'),
      '#default_value' => $this->getSetting('other_title'),
    );
    $form['other_unknown_defaults'] = array(
      '#type' => 'select',
      '#title' => t('<em>Other</em> value as default value'),
      '#description' => t("If any incoming default values do not appear in <em>available options</em> (i.e. set as <em>other</em> values), what should happen?"),
      '#options' => array(
        'other' => t('Add the values to the other textfield'),
        'append' => t('Append the values to the current list'),
        'available' => t('Append the values to the available options'),
        'ignore' => t('Ignore the values'),
      ),
      '#default_value' => $this->getSetting('other_unknown_defaults'),
      '#required' => TRUE,
    );
    $form['other_size'] = array(
      '#type' => 'number',
      '#title' => t('<em>Other</em> field size'),
      '#default_value' => $this->getSetting('other_size'),
      '#required' => TRUE,
    );
    $form['sort_options'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sort options'),
      '#description' => t("Sorts the options in the list alphabetically by value."),
      '#default_value' => $this->getSetting('sort_options'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += array(
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#other' => $this->getSetting('other'),
      '#other_title' => $this->getSetting('other_title'),
      '#other_size' => $this->getSetting('other_size'),
      '#other_delimiter' => FALSE,
      '#other_unknown_defaults' => $this->getSetting('other_unknown_defaults'),
      '#field_widget' => 'select_or_other',
      '#select_type' => 'select',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions() {
    if (!isset($this->options)) {
      $string_options = $this->getSetting('available_options');

      $string_options = trim($string_options);
      if (empty($string_options)) {
        return [];
      }
      // If option has a key specified
      if (strpos($string_options, '|') !== FALSE) {
        $options = [];
        $list = explode("\n", $string_options);
        $list = array_map('trim', $list);
        $list = array_filter($list, 'strlen');

        foreach ($list as $position => $text) {
          $value = $key = FALSE;

          // Check for an explicit key.
          $matches = array();
          if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
            $key = $matches[1];
            $value = $matches[2];
          }

          $options[$key] = (isset($value) && $value !== '') ? html_entity_decode($value) : $key;
        }
      }
      else {
        $options[$string_options] = html_entity_decode($string_options);
      }


      $label = t('N/A');

      // Add an empty option if the widget needs one.
      if ($empty_option = $this->getEmptyOption()) {
        switch ($this->getPluginId()) {
          case 'select_or_other_buttons':
            $label = t('N/A');
            break;

          case 'select_or_other':
          case 'select_or_other_sort':
            $label = ($empty_option == static::SELECT_OR_OTHER_EMPTY_NONE ? t('- None -') : t('- Select a value -'));
            break;
        }

        $options = array('_none' => $label) + $options;
      }

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
  static protected function sanitizeLabel(&$label) {
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
   * {@inheritdoc}
   */
  protected function getEmptyOption() {
    if ($this->isMultiple()) {
      // Multiple select: add a 'none' option for non-required fields.
      if (!$this->isRequired()) {
        return static::SELECT_OR_OTHER_EMPTY_NONE;
      }
    }
    else {
      // Single select: add a 'none' option for non-required fields,
      // and a 'select a value' option for required fields that do not come
      // with a value selected.
      if (!$this->isRequired()) {
        return static::SELECT_OR_OTHER_EMPTY_NONE;
      }
      if (!$this->hasValue()) {
        return static::SELECT_OR_OTHER_EMPTY_SELECT;
      }
    }
    return NULL;
  }

}
