<?php

namespace Drupal\datetime_range_timezone\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

/**
 * Plugin implementation of the 'daterange_timezone' widget.
 *
 * @FieldWidget(
 *   id = "daterange_timezone",
 *   label = @Translation("Date and time range (with timezone)"),
 *   field_types = {
 *     "daterange",
 *     "daterange_timezone"
 *   }
 * )
 */
class DateRangeTimezone extends DateRangeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Ensure that we're displaying the values in the edit form in the timezone
    // they selected and not the timezone from the site.
    if ($items[$delta]->timezone) {
      $timezone = new \DateTimeZone($items[$delta]->timezone);
      $element['value']['#default_value']->setTimeZone($timezone);
      $element['end_value']['#default_value']->setTimeZone($timezone);
    }

    $element['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Timezone'),
      '#options' => system_time_zones(NULL, TRUE),
      '#description' => $this->t('This should be the timezone you entered the above date in and will also be the timezone in which this event date is displayed.'),
      '#default_value' => $items[$delta]->timezone,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      if (empty($value['timezone'])) {
        continue;
      }

      $timezone = new \DateTimezone($value['timezone']);

      // We overwrite the values with the exact same time but with the timezone
      // set to the timezone the user told us they were using when entering the
      // dates. We do this here, and then DateRangeWidgetBase converts these
      // values into UTC for storage. This makes displaying the dates correctly
      // simple. We use the UTC stored date time plus the timezone stored.
      if ($values[$delta]['value'] instanceof DrupalDateTime) {
        $values[$delta]['value'] = new DrupalDateTime($values[$delta]['value']->format(DATETIME_DATETIME_STORAGE_FORMAT), $timezone);
        $values[$delta]['end_value'] = new DrupalDateTime($values[$delta]['end_value']->format(DATETIME_DATETIME_STORAGE_FORMAT), $timezone);
      }
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

}
