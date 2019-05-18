<?php

namespace Drupal\scheduling\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\scheduling\Plugin\Field\FieldWidget\RangeTrait;
use Drupal\scheduling\Plugin\Field\FieldWidget\RecurringTrait;

/**
 * Plugin implementation of the 'scheduling' widget.
 *
 * @FieldWidget(
 *   id = "scheduling_value",
 *   label = @Translation("Scheduling"),
 *   field_types = {
 *     "scheduling_value"
 *   }
 * )
 */
class SchedulingValueWidget extends WidgetBase {

  use RangeTrait;
  use RecurringTrait;

  /**
   * @inheritdoc
   */
  protected function formMultipleElements(
    FieldItemListInterface $items,
    array &$form,
    FormStateInterface $form_state
  ) {

    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $id_prefix = implode('-', array_merge($parents, [$field_name]));
    $wrapper_id = Html::cleanCssIdentifier($id_prefix . '-add-wrapper');

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $max = $field_state['items_count'] - 1;
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = ($cardinality > 1);
        break;
    }

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta]) && isset($field_state['increment'])) {
        $items->appendItem([
          'value' => [
            'mode' => $field_state['increment'],
          ],
        ]);
        unset($field_state['increment']);
        static::setWidgetState($parents, $field_name, $form_state,
          $field_state);
      }
    }

    $elements = parent::formMultipleElements($items, $form, $form_state);

    if ($elements['add_more']) {
      $elements['add_more'] = $this->addMoreElement($id_prefix, $wrapper_id);
    }

    // Get states.
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $id_prefix = implode('-', array_merge($parents, [$field_name]));
    $name = Html::cleanCssIdentifier(str_replace('value', 'mode', $id_prefix) . '_select');
    $elements['#attributes'] = [
      'class' => [
        'dingsbums'
      ],
    ];

    $elements['#attached']['library'][] = 'scheduling/scheduling';

    return $elements;
  }

  /**
   * @inheritdoc
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {

    // Get states.
    $field_name = $this->fieldDefinition->getName();
    $value = $items[$delta]->value;
    $parents = $form['#parents'];
    $id_prefix = implode('-', array_merge($parents, [$field_name]));

    foreach (['from', 'to'] as $field) {
      if (isset($value[$field])) {
        if (!($value[$field] instanceof DrupalDateTime)) {
          $value[$field] = new DrupalDateTime($value[$field]);
        }
      } else {
        $value[$field] = NULL;
      }
    }

    $name = Html::cleanCssIdentifier(str_replace('value', 'mode', $id_prefix) . '_select');

    if ($value['mode'] === 'range') {
      $element['value'] =  $this->buildRangeWidget($value, $id_prefix);
    }
    if ($value['mode'] === 'recurring') {
      $element['value'] =  $this->buildRecurringWidget($value, $id_prefix);
    }

    return $element;
  }

  /**
   * Submission handler for the "Add manual" button.
   */
  public static function addItemSubmit(
    array $form,
    FormStateInterface $form_state
  ) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form,
      array_slice($button['#array_parents'], 0, -2));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    // Register the item mode to add.
    $button_name = explode('_', $button['#name']);
    $field_state['increment'] = array_pop($button_name);;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addItemAjax(
    array $form,
    FormStateInterface $form_state
  ) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));

    // Ensure the widget allows adding additional items.
    if ($element['#cardinality'] !== FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      return;
    }

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $element['#max_delta'];
    $element[$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
    $element[$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';

    return $element;
  }

  public function massageFormValues(
    array $values,
    array $form,
    FormStateInterface $form_state
  ) {
    $values = parent::massageFormValues($values, $form, $form_state);
    // Account for single cardinality fields.
    if (count($values) === 1 && isset($values[0]['value']) && isset($values[0]['value']['single']) && $values[0]['value']['single'] === TRUE) {
      unset($values[0]['value']['single']);
      $values[0]['value'] = $values[0]['value'][$values[0]['value']['mode']];
    }
    foreach ($values as &$item) {

      if (isset($item['value']['from']) && $item['value']['from'] instanceof DrupalDateTime) {
        $item['value']['from'] = $item['value']['from']->render();
      }
      if (isset($item['value']['to']) && $item['value']['to'] instanceof DrupalDateTime) {
        $item['value']['to'] = $item['value']['to']->render();
      }

    }
    return $values;
  }

  /**
   * @param $id_prefix
   * @param $wrapper_id
   *
   * @return array
   */
  protected function addMoreElement($id_prefix, $wrapper_id) {
    $name = Html::cleanCssIdentifier(str_replace('value', 'mode', $id_prefix) . '_select');
    return [
      'add_range' => [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_range',
        '#value' => t('+ Item'),
        '#attributes' => [
          'class' => [
            'field-add-more-submit',
            'field-add-range-submit',
          ],
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'addItemSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'addItemAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ],
      'add_recurring' => [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_recurring',
        '#value' => t('+ Item'),
        '#attributes' => [
          'class' => [
            'field-add-more-submit',
            'field-add-recurring-submit',
          ],
        ],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'addItemSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'addItemAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'mode' => 'range',
      ] + parent::defaultSettings();
  }

}
