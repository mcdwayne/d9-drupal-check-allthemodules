<?php

namespace Drupal\jstimer\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'JsTimer' formatter for 'datetime' fields.
 *
 * @FieldFormatter(
 *   id = "jstimer_jst_timer",
 *   label = @Translation("JsTimer - Timer"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class JsTimerDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = array(
      'dir' => 'down',
      'format_txt' => ''
    ) + parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $date = $item->date;
      $output = [];
      if (!empty($item->date)) {
        if ($this->getFieldSetting('datetime_type') == 'date') {
          // A date without time will pick up the current time, use the default.
          datetime_date_default_time($date);
        }
        
        // $date is a DrupalDateTime object
        $args = $this->dateToWidget($date->getTimestamp(), "jst_timer", $this->getSettings());
        $output = jst_timer_show($args['widget_args']);
      }
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    
    $form['dir'] = array(
    '#title' => $this->t('Countdown or countup timer'),
    '#type' => 'select',
    '#options' => array('up' => t('Up'), 'down' => t('Down')),
    '#default_value' => $this->getSetting('dir'),
    '#weight' => 0,
    );

    $form['format_txt'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Format of the timer:'),
    '#default_value' => $this->getSetting('format_txt'),
    '#weight' => 1,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Displaying a countdown timer'); 
    
    return $summary;
  }


  protected function dateToWidget($unix_timestamp, $widget_name, $settings = array()) {
    $datetime = date_iso8601($unix_timestamp);
      
    $widget_args = array(
      'widget_name' => $widget_name,
      'widget_args' => array(
        'datetime' => $datetime
      )
    );
      
    foreach ($settings as $key => $val) {
      if ($val <> '') {
        $widget_args['widget_args'][$key] = $val;
      }
    }
    return $widget_args;
  }  
}
