<?php

namespace Drupal\date_time_defaults\Plugin\Field\FieldWidget;

use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'date_time_defaults' widget.
 *
 * @FieldWidget(
 *   id = "date_time_defaults_widget",
 *   label = @Translation("Date and time defaults"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class DateTimeDefaultsWidget extends DateTimeDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'date_time'=> '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // Enforce defaults, empty value creates current date/time, negative value gives defaults
    if (empty($this->getSetting('date_time')['date'])){
      $this->setSetting('date_time', ['date'=> '-1018-10-09', 'time'=> $this->getSetting('date_time')['time']]);
    }
    if (empty($this->getSetting('date_time')['time'])){
      $this->setSetting('date_time', ['date'=> $this->getSetting('date_time')['date'], 'time' => '-10:22:21']);
    }

    $elements['date_time'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Set time default'),
      '#default_value' => new DrupalDateTime($this->getSetting('date_time')['date'] . $this->getSetting('date_time')['time']),
      '#element_validate' => array(),
    ];

    return $elements;
  }


  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    if (!empty($this->getSetting('date_time')['date'])) {
      $summary[] = t('Set date: @date', ['@date' => $this->getSetting('date_time')['date']]);
    } else {
      $summary[] = t('Set date: Not set');
    }

    if (!empty($this->getSetting('date_time')['time'])) {
      $summary[] = t('Set time: @time', ['@time' => $this->getSetting('date_time')['time']]);
    } else {
      $summary[] = t('Set time: Not set');
    }
    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Enforce defaults, empty value creates current date/time, negative value gives defaults
    if (empty($this->getSetting('date_time')['date'])){
      $this->setSetting('date_time', ['date'=> '-1018-10-09', 'time'=> $this->getSetting('date_time')['time']]);
    }

    if (empty($this->getSetting('date_time')['time'])){
      $this->setSetting('date_time', ['date'=> $this->getSetting('date_time')['date'], 'time' => '-10:22:21']);
    }

    $default_date_time =  new DrupalDateTime($this->getSetting('date_time')['date'] . $this->getSetting('date_time')['time']);
    $element['value']['#default_value'] =    isset($items[$delta]->value) ? $element['value']['#default_value'] : new DrupalDateTime($default_date_time);

    return $element;
  }
}
