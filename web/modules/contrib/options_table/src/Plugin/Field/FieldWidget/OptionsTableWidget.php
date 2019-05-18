<?php

namespace Drupal\options_table\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'options_table' widget.
 *
 * @FieldWidget(
 *   id = "options_table",
 *   label = @Translation("Draggable Table"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *   },
 *   multiple_values = TRUE
 * )
 */
class OptionsTableWidget extends OptionsWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#multiple'] = $this->multiple;

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);

    // Sort options based on field delta values.
    $sorted_options = [];
    foreach ($selected as $value) {
      $sorted_options[$value] = $options[$value];
      unset($options[$value]);
    }
    $sorted_options = $sorted_options + $options;

    // If required and there is one single option, preselect it.
    if ($this->required && count($options) == 1) {
      reset($options);
      $selected = [key($options)];
    }

    $element['table'] = [
      '#type' => 'table',
      '#header' => [
        $element['#title'],
        [
          'data' => $this->t('Enabled'),
          'class' => ['checkbox'],
        ],
        $this->t('Weight'),
      ],
      '#rows' => [],
      '#attributes' => [
        'class' => [Html::getClass($this->fieldDefinition->getName() . '-table')],
        'id' => Html::getUniqueId($this->fieldDefinition->getName() . '-table'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'option-weight',
        ],
      ],
    ];

    $index = 0;
    $delta = count($sorted_options);
    foreach ($sorted_options as $option => $label) {
      $element['table'][$option]['label'] = ['#markup' => $label];
      if ($this->multiple) {
        $element['table'][$option]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#default_value' => in_array($option, $selected),
        ];
      }
      else {
        $parents = [
          $this->fieldDefinition->getName(),
          'table',
          'enabled',
        ];
        $parents_for_id = array_merge($parents, [$option]);

        $element['table'][$option]['enabled'] = [
          '#type' => 'radio',
          '#title' => $this->t('Enabled'),
          '#parents' => $parents,
          '#id' => Html::getUniqueId('edit-' . implode('-', $parents_for_id)),
          '#return_value' => $option,
          '#title_display' => 'invisible',
          '#default_value' => in_array($option, $selected) ? $option : FALSE,
        ];
      }
      $element['table'][$option]['weight'] = [
        '#type' => 'weight',
        '#delta' => $delta,
        '#default_value' => $index,
        '#title' => $this->t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
      ];
      $element['table'][$option]['weight']['#attributes']['class'] = ['option-weight'];
      $element['table'][$option]['#attributes']['class'][] = 'draggable';
      $index++;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $selected_options = [];

    if ($element['#multiple']) {
      foreach (Element::children($element['table']) as $item) {
        if ($element['table'][$item]['enabled']['#value']) {
          $selected_options[$element['table'][$item]['weight']['#value']] = $item;
        }
      }
    }
    else {
      foreach (Element::children($element['table']) as $item) {
        $selected_options[] = $element['table'][$item]['enabled']['#value'];
        break;
      }
    }

    if ($element['#required'] && empty($selected_options)) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }

    // Filter out the 'none' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('_none', $selected_options, TRUE);
    if ($index !== FALSE) {
      unset($selected_options[$index]);
    }

    // Transpose selections from field => delta to delta => field.
    $items = [];
    ksort($selected_options);
    foreach ($selected_options as $value) {
      $items[] = [$element['#key_column'] => $value];
    }
    $form_state->setValueForElement($element, $items);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyLabel() {
    if (!$this->required && !$this->multiple) {
      return t('N/A');
    }
  }

}
