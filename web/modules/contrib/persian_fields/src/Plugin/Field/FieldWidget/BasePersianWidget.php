<?php


namespace Drupal\persian_fields\Plugin\Field\FieldWidget;


use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

abstract class BasePersianWidget extends WidgetBase {

  protected function formSingleElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formSingleElement($items, $delta, $element, $form, $form_state);
    $element['#attached']['library'][] = 'persian_fields/persian_fields.main';
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);

    if ($key_exists) {
      // Account for drag-and-drop reordering if needed.
      if (!$this->handlesMultipleValues()) {
        // Remove the 'value' of the 'add more' button.
        unset($values['add_more']);

        // The original delta, before drag-and-drop reordering, is needed to
        // route errors to the correct form element.
        foreach ($values as $delta => &$value) {
          $value['_original_delta'] = $delta;
        }

        usort($values, function ($a, $b) {
          return SortArray::sortByKeyInt($a, $b, '_weight');
        });
      }

      // Let the widget massage the submitted values.
      $values = $this->massageFormValues($values, $form, $form_state);

      // Assign the values and remove the empty ones.
      if (count($values) && key_exists('value', $values[0])) {
        $values[0]['value'] = $this->toPersian(preg_replace('/\s+/', '', $values[0]['value']));
      }
      $items->setValue($values);
      $items->filterEmptyItems();

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = isset($item->_original_delta) ? $item->_original_delta : $delta;
        unset($item->_original_delta, $item->_weight);
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  private function toPersian($string) {
    $characters = [
      '١' => '۱',
      '٢' => '۲',
      '٣' => '۳',
      '٤' => '۴',
      '٥' => '۵',
      '٦' => '۶',
      '٧' => '۷',
      '٨' => '۸',
      '٩' => '۹',
      '٠' => '۰',
    ];

    $string = str_replace(array_keys($characters), array_values($characters), $string);

    $characters = [
      '۱' => '1',
      '۲' => '2',
      '۳' => '3',
      '۴' => '4',
      '۵' => '5',
      '۶' => '6',
      '۷' => '7',
      '۸' => '8',
      '۹' => '9',
      '۰' => '0',
    ];

    return str_replace(array_keys($characters), array_values($characters), $string);
  }
}