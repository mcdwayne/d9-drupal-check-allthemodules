<?php

namespace Drupal\datetime_range_ef\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;
/**
 * Plugin implementation of the 'carga_view' formatter.
 *
 * @FieldFormatter(
 *   id = "daterange_ef",
 *   label = @Translation("Date difference"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateDiffFormatter extends DateRangeDefaultFormatter {

  /**
  * {@inheritdoc}
  */
  public static function defaultSettings() {
    return [
      'output_format' => 'full',
      'work_as_duration' => 1,
      'difference_separator' => '+',
      'difference_format' => '\'Y, \' \'M, \' \'D\' \' (X)\'',
    ] + parent::defaultSettings();
  }

  /**
  * Helper function to get the formatter settings options.
  *
  * @return array
  *   The formatter settings options.
  */
  protected function formatOptions() {
    return [
      'raw' => $this->t("Raw result"),
      'full' => $this->t("Full formatted difference"),
      'datePlusDays' => $this->t("Start date +days"),
      'dateAndDiff' => $this->t("Dates and difference"),
    ];
  }



  /**
   * Returns the output format, set or default one.
   *
   * @return string
   *   The output format string.
   */
  protected function getOutputFormat() {
    return in_array($this->getSetting('output_format'), array_keys($this->formatOptions())) ? $this->getSetting('output_format') : self::defaultSettings()['output_format'];
  }

  /**
  * {@inheritdoc}
  */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['output_format'] = array (
      '#title' => $this->t('Style for the result'),
      '#type' => 'select',
      '#default_value' => $this->getOutputFormat(),
      '#options' => $this->formatOptions(),
      '#description' => $this->t('Style to use for the result'),
      '#required' => TRUE,
      '#weight' => 10,
    );
    $form['work_as_duration'] = array(
      '#type' => 'checkbox',
      '#title' => t('Duration. Will add +1 day.'),
      '#default_value' => $this->getSetting('work_as_duration'),
      '#weight' => 20,
    );
    $form['separator']['#states'] = array (
      'visible' => array(
        ':input[name="options[settings][output_format]"]' => array(
          'value' => 'dateAndDiff',
        ),
      ),
    );
    $form['separator']['#weight'] = 30;
    $form['difference_separator'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Separator, date from difference'),
      '#description' => $this->t('The string to separate the start and the difference'),
      '#default_value' => $this->getSetting('difference_separator'),
      '#weight' => 40,
      '#states' => array (
        'visible' => array(
          ':input[name="options[settings][output_format]"]' => array(
            array('value' => 'datePlusDays'),
            array('value' => 'dateAndDiff'),
          ),
        ),
      ),
    );
    $form['difference_format'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('Output format for the difference'),
      '#description' => $this->t('\'Y\', \'M\', \'D\' and \'X\' combinations DOUBLEQUOTED. Check the <a href="/admin/help/datetime_range_ef">Help page</a> for instructions'),
      '#default_value' => $this->getSetting('difference_format'),
      '#weight' => 50,
      '#states' => array (
        'visible' => array(
          ':input[name="options[settings][output_format]"]' => array(
            array('value' => 'full'),
            array('value' => 'dateAndDiff'),
          ),
        ),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $time_zone = drupal_get_user_timezone();
    $time_zone = new \DateTimeZone($time_zone);

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date)) {
        if (empty($item->end_date) || is_null($item->end_date)) {
          $item->end_date = new DrupalDateTime(date());
          $today = TRUE;
        }
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        $start_date->setTimezone($time_zone);
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;
        $end_date->setTimezone($time_zone);

        if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
          $date_diff = $this->calculateDiff($start_date, $end_date, bool &$today);

          $elements[$delta] = [
            '#type' => 'markup',
            '#markup' => $date_diff,
            '#cache' => ['contexts' => ['timezone',],],
          ];
        }
        else {
          if ($this->getSetting('work_as_duration') == 1){
            $elements[$delta] = [
              '#type' => 'markup',
              '#markup' => '1' . t('day'),
              '#cache' => [
                'contexts' => [
                  'timezone',
                ],
              ],
            ];
          }
          else {
            $elements[$delta] = $this->buildDate($item->start_date);
            if (!empty($item->_attributes)) {
              $elements[$delta]['#attributes'] += $item->_attributes;
              // Unset field item attributes since they have been included in the
              // formatter output and should not be rendered in the field template.
              unset($item->_attributes);
            }
          }
        }
      }
    }

    return $elements;
  }

  private function calculateDiff($start_date, $end_date) {
    $difference_separator = $this->getSetting('difference_separator');
    $output_format = $this->getSetting('output_format');
    //$date_format = \Drupal::entityTypeManager()->getStorage('date_format')->load($format_type);
    $date_diff = $end_date->getTimestamp() - $start_date->getTimestamp();
    $day_diff = floor($date_diff / (60 * 60 * 24)) + $this->getSetting('work_as_duration');
    $day_rest = $day_diff;
    /*$markup = '';
    $year_part = '';
    $month_part = '';
    $day_part = '';*/
    if ($output_format === 'datePlusDays') {
      return $markup = $this->buildDate($start_date)['#markup'] . ' ' . $difference_separator . ' ' . $day_diff . ' ' . t('days');
    }
    if ($output_format === 'raw') {
      return $markup = $day_diff;
    }
    if ($output_format === 'full' || $output_format === 'dateAndDiff') {
      if ($output_format === 'dateAndDiff') {
        $markup .= $this->buildDate($start_date)['#markup'];
        $markup .= ' ' . $this->getSetting('separator'). ' ';
        $markup .= $today?t('Today'):$this->buildDate($end_date)['#markup'];
        $markup .= ' ' . $difference_separator . ' ';
      }
      if ($day_diff >= 365 ) {
        $year_diff = floor($day_diff/365);
        $day_rest =  $day_rest - ($year_diff*365);
      }
      else {
        $year_diff = 0;
      }
      if ($day_rest >= 30) {
        $month_diff = floor($day_rest/30);
        $day_rest =  $day_rest - ($month_diff*30);
      }
      else {
        $month_diff = 0;
      }

      $format_array = get_delimited($this->getSetting('difference_format'));
      foreach ($format_array as $key => $value) {
        if (strpos($value,'X')) {
          $print_diff = str_replace('X', $day_diff === 1?'1 ' . t('Day'):$day_diff . ' ' . t('Days') , $value);
          unset($format_array[$key]);
        }
        if (strpos($value,'Y') !== FALSE) {
          if ($year_diff != 0) {
            $value = str_replace('Y', $year_diff === 1?'1 ' . t('Year'):$year_diff . ' ' . t('Years'), $value);
            $year_diff = 0;
          }
          else {
            $value='';
          }
          $format_array[$key] = $value;
        }
        if (strpos($value,'M') !== FALSE) {
          if ($year_diff != 0) {
            $month_diff = $month_diff + $year_diff*30;
          }
          if ($month_diff != 0) {
            $value = str_replace('M', $month_diff === 1?'1 ' . t('Month'):$month_diff . ' ' . t('Months'), $value);
            $month_diff = 0;
          }
          else {
            $value='';
          }
          $format_array[$key] = $value;
        }
        if (strpos($value,'D') !== FALSE) {
          if ($month_diff != 0) {
            $day_rest = $day_rest + $month_diff*30;
          }
          if ($day_rest != 0) {
            $value = str_replace('D', $day_rest === 1?'1 ' . t('Day'):$day_rest . ' ' . t('Days'), $value);
            $day_part = '';
            //$value = str_replace('"', '', $value);
          }
          else {
            $value='';
          }
          $format_array[$key] = $value;
        }
      }

      $markup .= trim(implode($format_array),' +-,') . $print_diff;

      return $markup;
    }

    $elements[$delta] = $this->buildDate($item->start_date);
  }
}
function get_delimited($str, $delimiter='"') {
    $escapedDelimiter = preg_quote($delimiter, '/');
    if (preg_match_all('/' . $escapedDelimiter . '(.*?)' . $escapedDelimiter . '/s', $str, $matches)) {
        return $matches[1];
    }
}
