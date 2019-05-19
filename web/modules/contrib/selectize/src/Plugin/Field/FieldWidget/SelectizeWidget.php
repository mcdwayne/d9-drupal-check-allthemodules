<?php

namespace Drupal\selectize\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'selectize_widget' widget.
 *
 * @FieldWidget(
 *   id = "selectize_widget",
 *   label = @Translation("Selectize"),
 *   label_singular = @Translation("Selectize"),
 *   label_plural = @Translation("Selects"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("selectize"),
 *     plural = @Translation("selects"),
 *   ),
 *   field_types = {
 *     "list_string",
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class SelectizeWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'create' => FALSE,
      'sortField' => 'text',
      'allowEmptyOption' => TRUE,
      'plugins' => ['remove_button'],
      'highlight' => TRUE,
      'persist' => FALSE,
      'diacritics' => FALSE,
      'closeAfterSelect' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['plugins'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Plugins'),
      '#description' => $this->t('For a demonstration of what these plugins do, visit the <a href="@url">demo site</a>.', ['@url' => 'https://selectize.github.io/selectize.js/']),
      '#default_value' => $this->getSetting('plugins'),
      '#options' => [
        'remove_button' => $this->t('Remove Button'),
        'drag_drop' => $this->t('Drag & Drop'),
        'restore_on_backspace' => $this->t('Restore on Backspace'),
        'optgroup_columns' => $this->t('Optgroup Columns'),
        'dropdown_header' => $this->t('Dropdown Headers')
      ]
    );

    $element['closeAfterSelect'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Close after selecting an option'),
      '#description' => $this->t('This will close the Selectized display after choosing a value.'),
      '#default_value' => $this->getSetting('closeAfterSelect'),
    );

    $element['diacritics'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Diacritics Support'),
      '#description' => $this->t('Enable or disable international character support'),
      '#default_value' => $this->getSetting('diacritics'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $plugins = $this->getSetting('plugins');

    if (!empty($plugins)) {
      $summary[] = $this->t('Enabled plugins: @plugins', array('@plugins' => implode(', ', $plugins)));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $settings = $this->getSettings();
    $settings['plugins'] = array_keys(array_filter($settings['plugins']));
    $settings['maxItems'] = ($cardinality >= 1) ? $cardinality : 999;
    $settings['items'] = $this->getSelectedOptions($items, $delta);

    $element += array(
      '#type' => 'selectize',
      '#settings' => $settings,
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOptions($items, $delta),
      '#multiple' => $this->multiple && count($this->options) > 1,
    );

    $element['#attributes']['placeholder'] = $this->t('Start typing to see a list of options...');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));

    // Replace hyphens at the start of an item, example, taxonomy children.
    $label = preg_replace('/^-+/', '', $label);
  }

}
