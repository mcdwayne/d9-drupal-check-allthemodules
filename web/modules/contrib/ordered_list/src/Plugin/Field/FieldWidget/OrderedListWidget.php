<?php

namespace Drupal\ordered_list\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ordered_list\Element\OrderedList;

/**
 * Plugin implementation of the 'ordered_list' widget.
 *
 * @FieldWidget(
 *   id = "ordered_list",
 *   label = @Translation("Ordered list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OrderedListWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $list = [
      '#type' => 'ordered_list',
      '#title_display' => 'invisible',
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $this->getSelectedOptions($items),
      '#labels' => $this->getSetting('labels'),
    ] + parent::formElement($items, $delta, $element, $form, $form_state);
    return [
      '#type' => 'details',
      '#title' => $element['#title'],
      '#open' => $this->getSetting('open'),
      'list' => $list,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values['list'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'open' => TRUE,
      'labels' => OrderedList::defaultLabels(),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display element open by default.'),
      '#default_value' => $this->getSetting('open'),
    ];
    $titles = [
      'items_available' => $this->t('Available items label'),
      'items_selected' => $this->t('Selected items label'),
      'control_select' => $this->t('Select control label'),
      'control_deselect' => $this->t('Deselect control label'),
      'control_moveup' => $this->t('Move up control label'),
      'control_movedown' => $this->t('Move down control label'),
    ];
    $labels = $this->getSetting('labels');
    foreach (array_keys(OrderedList::defaultLabels()) as $key) {
      $element['labels'][$key] = [
        '#type' => 'textfield',
        '#title' => $titles[$key],
        '#default_value' => $labels[$key],
        '#required' => TRUE,
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $open = $this->getSetting('open');
    $summary[] = $this->t('Default state: @state', [
      '@state' => $open ? $this->t('Open') : $this->t('Closed'),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    return NULL;
  }

}
