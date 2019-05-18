<?php

namespace Drupal\daterange_compact\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for editing date range formats.
 *
 * @package Drupal\daterange_compact\Form
 */
class DateRangeFormatForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\daterange_compact\Entity\DateRangeFormatInterface $format */
    $format = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $format->label(),
      '#description' => $this->t("Name of the date time range format."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $format->id(),
      '#machine_name' => [
        'exists' => '\Drupal\daterange_compact\Entity\DateRangeFormat::load',
      ],
      '#disabled' => !$format->isNew(),
    ];

    $date_settings = $format->getDateSettings();

    $form['date'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Date only formats'),
      '#tree' => FALSE,
    ];

    $form['date']['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic'),
      '#open' => TRUE,
      '#weight' => 1,
      '#group' => 'date',
      '#description' => $this->t('Basic date format used for single dates, or ranges that cannot be shown in a compact form.'),
    ];

    $form['date']['basic']['default_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#default_value' => $date_settings['default_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#required' => TRUE,
      '#parents' => ['date_settings', 'default_pattern'],
    ];

    $form['date']['basic']['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $date_settings['separator'] ?: '',
      '#maxlength' => 100,
      '#size' => 10,
      '#description' => $this->t('Text between start and end dates.'),
      '#required' => FALSE,
      '#parents' => ['date_settings', 'separator'],
    ];

    $form['date']['same_month'] = [
      '#type' => 'details',
      '#title' => $this->t('Same month'),
      '#open' => TRUE,
      '#weight' => 2,
      '#group' => 'date',
      '#description' => $this->t('Optional formatting of date ranges that span multiple days within the same month.'),
    ];

    $form['date']['same_month']['same_month_start_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start date pattern'),
      '#default_value' => $date_settings['same_month_start_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#parents' => ['date_settings', 'same_month_start_pattern'],
    ];

    $form['date']['same_month']['same_month_end_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End date pattern'),
      '#default_value' => $date_settings['same_month_end_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#parents' => ['date_settings', 'same_month_end_pattern'],
    ];

    $form['date']['same_year'] = [
      '#type' => 'details',
      '#title' => $this->t('Same year'),
      '#open' => TRUE,
      '#weight' => 2,
      '#group' => 'date',
      '#description' => $this->t('Optional formatting of date ranges that span multiple months within the same year.'),
    ];

    $form['date']['same_year']['same_year_start_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start date pattern'),
      '#default_value' => $date_settings['same_year_start_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#parents' => ['date_settings', 'same_year_start_pattern'],
    ];

    $form['date']['same_year']['same_year_end_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End date pattern'),
      '#default_value' => $date_settings['same_year_end_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#parents' => ['date_settings', 'same_year_end_pattern'],
    ];

    $datetime_settings = $format->getDateTimeSettings();

    $form['datetime'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Date & time formats'),
    ];

    $form['datetime']['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic'),
      '#open' => TRUE,
      '#weight' => 1,
      '#group' => 'datetime',
      '#description' => $this->t('Basic date and time format used for single date/times, or ranges that cannot be shown in a compact form.'),
    ];

    $form['datetime']['basic']['default_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#default_value' => $datetime_settings['default_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#required' => TRUE,
      '#parents' => ['datetime_settings', 'default_pattern'],
    ];

    $form['datetime']['basic']['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $datetime_settings['separator'] ?: '',
      '#maxlength' => 100,
      '#size' => 10,
      '#description' => $this->t('Text between start and end date/times.'),
      '#required' => FALSE,
      '#parents' => ['datetime_settings', 'separator'],
    ];

    $form['datetime']['same_day'] = [
      '#type' => 'details',
      '#title' => $this->t('Same day'),
      '#open' => TRUE,
      '#weight' => 2,
      '#group' => 'datetime',
      '#description' => $this->t('Optional formatting of time ranges within a single day.'),
    ];

    $form['datetime']['same_day']['same_day_start_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start date/time pattern'),
      '#default_value' => $datetime_settings['same_day_start_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#parents' => ['datetime_settings', 'same_day_start_pattern'],
    ];

    $form['datetime']['same_day']['same_day_end_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End date/time pattern'),
      '#default_value' => $datetime_settings['same_day_end_pattern'] ?: '',
      '#maxlength' => 100,
      '#description' => $this->t('A user-defined date format. See the <a href="http://php.net/manual/function.date.php">PHP manual</a> for available options.'),
      '#parents' => ['datetime_settings', 'same_day_end_pattern'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $date_range_format = $this->entity;
    $status = $date_range_format->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label date range format.', [
          '%label' => $date_range_format->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Updated the %label date range format.', [
          '%label' => $date_range_format->label(),
        ]));
    }
    $form_state->setRedirectUrl($date_range_format->toUrl('collection'));
  }

}
