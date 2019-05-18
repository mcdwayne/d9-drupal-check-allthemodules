<?php

namespace Drupal\httpbl\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;


/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("host_cron_expire")
 */
class CronExpireDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $date_formats = array();
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', array('@name' => $value->label(), '@date' => $this->dateFormatter->format(\Drupal::time()->getRequestTime(), $machine_name)));
    }

    $form['date_format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats + array(
        // Added option.
        'cron time expire' => $this->t('Cron expire date countdown (0 = "(next cron)")'),
      ),
      '#default_value' => isset($this->options['date_format']) ? $this->options['date_format'] : 'small',
    );
    // Setup #states for 'cron time expire' form element.
    foreach (array('cron time expire') as $custom_date_possible) {
      $form['custom_date_format']['#states']['visible'][] = array(
        ':input[name="options[date_format]"]' => array('value' => $custom_date_possible),
      );
    }
    foreach (array_merge(array('custom'), array_keys($date_formats)) as $timezone_date_formats) {
      $form['timezone']['#states']['visible'][] = array(
        ':input[name="options[date_format]"]' => array('value' => $timezone_date_formats),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $format = $this->options['date_format'];

    if (in_array($format, array('cron time expire'))) {
      $custom_format = $this->options['custom_date_format'];
    }

    if ($value) {
      $timezone = !empty($this->options['timezone']) ? $this->options['timezone'] : NULL;
      $time_diff = \Drupal::time()->getRequestTime() - $value; // will be positive for a datetime in the past (ago), and negative for a datetime in the future (hence)
      switch ($format) {
        case 'cron time expire':
          if ($time_diff >= 0) {
            // Instead of showing "0 seconds"...
            return $this->t('(next cron)');
          }
          else {
            return $this->t('in ' . $this->dateFormatter->formatTimeDiffUntil($value, array('granularity' => is_numeric($custom_format) ? $custom_format : 2)));
          }

        default:
          return format_date($value, $format, '', $timezone);
      }
    }

    parent::render($values);
  }

}
