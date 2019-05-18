<?php

namespace Drupal\contacts_events\Plugin\Field\FieldWidget;

use Drupal\commerce_price\Plugin\Field\FieldWidget\PriceDefaultWidget;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'price_map' widget.
 *
 * @FieldWidget(
 *   id = "price_map",
 *   label = @Translation("Price map"),
 *   field_types = {
 *     "price_map"
 *   }
 * )
 */
class PriceMapWidget extends PriceDefaultWidget {

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItemList $items */
    $title = $this->fieldDefinition->getLabel();

    // Get the booking windows and ticket classes.
    $booking_windows = $items->getBookingWindows();
    $classes = $items->getClasses();

    // Check for any missing configuration.
    if (!$booking_windows || $booking_windows->count() == 0 || empty($classes)) {
      $elements = [
        'message' => [
          '#type' => 'item',
          '#title' => $this->fieldDefinition->getLabel(),
          '#markup' => $this->t('Please configure booking windows and classes before configuring %title.', [
            '%title' => $title,
          ]),
        ],
      ];
      foreach ($items as $delta => $item) {
        $elements[$delta] = [
          '#type' => 'value',
          '#value' => $items->getValue(),
        ];
      }
      return $elements;
    }

    // Get the price map.
    /* @var \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItem[][] $price_map */
    $price_map = $items->getPriceMap();

    // Display the price map in a table.
    $elements = [
      '#type' => 'table',
      '#header' => [''],
    ];

    // Populate our booking window headers.
    foreach ($booking_windows as $booking_window) {
      $elements['#header'][$booking_window->id] = $booking_window->label;
    }

    foreach ($classes as $class) {
      $class_id = $class->id();
      $class_label = $class->label();
      $elements[$class_id]['class'] = ['#markup' => $class_label];

      foreach ($booking_windows as $booking_window) {
        $element = [
          '#title' => $this->t('@title [Booking window: @booking_window Class: @class', [
            '@title' => $title,
            '@booking_window' => $booking_windows->label,
            '@class' => $class_label,
          ]),
          '#title_display' => 'invisible',
          '#descrption' => '',
        ];

        if (!isset($price_map[$booking_window->id][$class_id])) {
          $price_map[$booking_window->id][$class_id] = $items->appendItem([
            'booking_window' => $booking_window->id,
            'class' => $class_id,
          ]);
        }
        $delta = $price_map[$booking_window->id][$class_id]->getName();

        $elements[$class_id][$booking_window->id] = $this->formSingleElement($items, $delta, $element, $form, $form_state);
      }
    }
    $elements['#process'][] = [$this, 'processTable'];

    return $elements;
  }

  /**
   * Processes the table to set up the correct parents.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processTable(array &$element, FormStateInterface $form_state, array &$complete_form) {
    foreach (Element::children($element) as $row_id) {
      foreach (Element::children($element[$row_id]) as $column_id) {
        $price_element = &$element[$row_id][$column_id];
        if (isset($price_element['#delta'])) {
          $price_element['#parents'] = $element['#parents'];
          $price_element['#parents'][] = $price_element['#delta'];
        }
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#type'] = 'price_map_item';
    $element['#booking_window'] = $items[$delta]->booking_window;
    $element['#class'] = $items[$delta]->class;
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
    $values = &NestedArray::getValue($form_state->getValues(), $path, $key_exists);
    unset($values['message']);

    return parent::extractFormValues($items, $form, $form_state);
  }

}
