<?php

/**
 * @file
 * Contains \Drupal\nepali_calendar\Form\NepaliCalendarSettingsForm.
 */

namespace Drupal\nepali_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class NepaliCalendarSettingsForm.
 */
class NepaliCalendarSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nepali_calendar.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nepali_calendar_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    // Instantiate a Config object by calling the function config() with the
    // filename minus the extension.
    $config = $this->config('nepali_calendar.settings');

    $form['overview'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('Converts Gregorian calendar (A.D.) to Bikram Sambat (B.S.) and vice-versa.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );

    $form['general'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array('nepali_calendar/nepali_calendar.admin'),
      ),
    );

    $form['general_nepali_date'] = array(
      '#type' => 'details',
      '#title' => $this->t('Nepali date'),
      '#description' => $this->t('<a href="@block">Nepali date</a> block is created automatically when "Nepali calendar" module is installed.', array('@block' => $base_url . '/admin/structure/block')),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#group' => 'general',
    );

    $form['general_nepali_date']['nepali_calendar_nepali_date_format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Nepali date format'),
      '#description' => $this->t('Date format that will be displayed in the user screen.'),
      '#options' => array(
        0 => nepali_calendar_translate_digit('2067') . ' ' . $this->t('Falgun') . ' ' . nepali_calendar_translate_digit('29'),
        1 => nepali_calendar_translate_digit('2067') . ', ' . $this->t('Falgun') . ' ' . nepali_calendar_translate_digit('29'),
        2 => nepali_calendar_translate_digit('2067') . ' ' . $this->t('Falgun') . ' ' . nepali_calendar_translate_digit('29') . ', ' . $this->t('Sunday'),
        3 => nepali_calendar_translate_digit('2067') . ', ' . $this->t('Falgun') . ' ' . nepali_calendar_translate_digit('29') . ', ' . $this->t('Sunday'),
        4 => t('Falgun') . ' ' . nepali_calendar_translate_digit('29') . ', ' . nepali_calendar_translate_digit('2067'),
        5 => t('Sunday') . ', ' . $this->t('Falgun') . ' ' . nepali_calendar_translate_digit('29') . ', ' . nepali_calendar_translate_digit('2067'),
        6 => nepali_calendar_translate_digit('2067') . '/' . nepali_calendar_translate_digit('11') . '/' . nepali_calendar_translate_digit('29'),
      ),
      '#default_value' => $config->get('nepali_calendar_nepali_date_format'),
      '#group' => 'general_nepali_date',
    );

    $form['general_nepali_date']['nepali_calendar_show_date_label'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show date label'),
      '#description' => $this->t('If checked, a date label "<strong>@date_label: </strong>" will be prepended to the date.', array('@date_label' => $this->t('Date'))),
      '#default_value' => $config->get('nepali_calendar_show_date_label'),
      '#group' => 'general_nepali_date',
    );

    $form['general_nepal_time'] = array(
      '#type' => 'details',
      '#title' => $this->t('Nepal time'),
      '#group' => 'general',
    );

    $form['general_nepal_time']['nepali_calendar_show_nepal_time'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show Nepal time'),
      '#description' => $this->t('If checked, Nepal time will be appended next to date.<br />E.g., 2067, Falgun 29 <strong>20:00</strong>'),
      '#default_value' => $config->get('nepali_calendar_show_nepal_time'),
      '#group' => 'general_nepal_time',
    );

    $form['general_nepal_time']['nepali_calendar_nepal_time_format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select time format'),
      '#options' => array(
        0 => $this->t('08:00 am/pm (12-hour format)'),
        1 => $this->t('08:00 AM/PM (12-hour format)'),
        2 => $this->t('20:00 (24-hour format)'),
      ),
      '#default_value' => $config->get('nepali_calendar_nepal_time_format'),
      '#states' => array(
        'invisible' => array(
          ':input[name="nepali_calendar_show_nepal_time"]' => array('checked' => FALSE),
        ),
      ),
      '#group' => 'general_nepal_time',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nepali_calendar.settings');

    // Set the configuration data into a config object and save it.
    $config
      ->set('nepali_calendar_nepali_date_format', $form_state->getValue('nepali_calendar_nepali_date_format'))
      ->set('nepali_calendar_show_date_label', $form_state->getValue('nepali_calendar_show_date_label'))
      ->set('nepali_calendar_show_nepal_time', $form_state->getValue('nepali_calendar_show_nepal_time'))
      ->set('nepali_calendar_nepal_time_format', $form_state->getValue('nepali_calendar_nepal_time_format'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
