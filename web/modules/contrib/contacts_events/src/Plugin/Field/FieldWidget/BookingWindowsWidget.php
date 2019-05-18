<?php

namespace Drupal\contacts_events\Plugin\Field\FieldWidget;

use Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItemList;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'booking_windows' widget.
 *
 * @FieldWidget(
 *   id = "booking_windows",
 *   label = @Translation("Booking windows"),
 *   field_types = {
 *     "booking_windows"
 *   }
 * )
 */
class BookingWindowsWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    // Don't show the additional element by default.
    unset($elements[$elements['#max_delta']]);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#process'][] = static::class . '::processElement';

    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $items[$delta]->label ?? NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
      '#weight' => 0,
    ];

    $element['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $items[$delta]->id ?? NULL,
      '#machine_name' => [
        'exists' => BookingWindowsItemList::class . '::checkUnique',
      ],
      '#disabled' => isset($items[$delta]->id),
    ];

    // Pass on the for the cut off date.
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['cut_off'] = $main_widget['value'];
    $element['cut_off']['#title'] = $this->t('Cut off');
    $element['cut_off']['#required'] = FALSE;
    $element['cut_off']['#weight'] = 1;

    return $element;
  }

  /**
   * Processes the element to set up the correct source for the ID.
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
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element['id']['#machine_name']['source'] = $element['#array_parents'];
    $element['id']['#machine_name']['source'][] = 'label';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timezone and format.
    foreach ($values as &$item) {
      if (!empty($item['cut_off']) && $item['cut_off'] instanceof DrupalDateTime) {
        $date = $item['cut_off'];
        switch ($this->getFieldSetting('datetime_type')) {
          case DateTimeItem::DATETIME_TYPE_DATE:
            $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
            break;

          default:
            $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
        $item['cut_off'] = $date->format($format);
      }
    }
    return $values;
  }

}
