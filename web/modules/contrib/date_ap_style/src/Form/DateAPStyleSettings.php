<?php

namespace Drupal\date_ap_style\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DateAPStyleSettings.
 */
class DateAPStyleSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'date_ap_style.dateapstylesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'date_ap_style_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('date_ap_style.dateapstylesettings');
    $form['date_ap_style_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AP Style Date Display Settings'),
      '#description' => $this->t('Configure AP date style default settings when using the AP date style format.'),
    ];
    $form['date_ap_style_settings']['always_display_year'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always display year'),
      '#description' => $this->t('When unchecked, the year will not be displayed if the date is in the same year as the current date.'),
      '#default_value' => $config->get('always_display_year'),
    ];
    $form['date_ap_style_settings']['use_today'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use today'),
      '#default_value' => $config->get('use_today'),
    ];
    $form['date_ap_style_settings']['cap_today'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize today'),
      '#default_value' => $config->get('cap_today'),
    ];
    $form['date_ap_style_settings']['display_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display day of the week'),
      '#default_value' => $config->get('display_day'),
      '#description' => $this->t('Display the day of the week when the date is in the same week as the current date. (Not available for date range fields.)'),
    ];
    $form['date_ap_style_settings']['display_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time'),
      '#default_value' => $config->get('display_time'),
    ];
    $form['date_ap_style_settings']['time_before_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display time before date'),
      '#description' => $this->t('When checked, the time will be displayed before the date. Otherwise it will be displayed after the date.'),
      '#default_value' => $config->get('time_before_date'),
      '#states' => [
        'visible' => [
          ':input[name="display_time"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['date_ap_style_settings']['use_all_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "All Day" instead of midnight'),
      '#default_value' => $config->get('display_noon_and_midnight'),
      '#states' => [
        'visible' => [
          ':input[name="display_time"]' => ['checked' => TRUE],
        ],
        'unchecked' => [
          ':input[name="display_noon_and_midnight"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['date_ap_style_settings']['display_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display noon and midnight'),
      '#description' => $this->t('Converts 12:00 p.m. to &quot;noon&quot; and 12:00 a.m. to &quot;midnight.&quot;'),
      '#default_value' => $config->get('display_noon_and_midnight'),
      '#states' => [
        'visible' => [
          ':input[name="display_time"]' => ['checked' => TRUE],
        ],
        'unchecked' => [
          ':input[name="use_all_day"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['date_ap_style_settings']['capitalize_noon_and_midnight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capitalize noon and midnight'),
      '#default_value' => $config->get('capitalize_noon_and_midnight'),
      '#states' => [
        'visible' => [
          ':input[name="display_time"]' => ['checked' => TRUE],
          ':input[name="display_noon_and_midnight"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['date_ap_style_settings']['separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Date range separator'),
      '#options' => [
        'endash' => $this->t('En dash'),
        'to' => $this->t('to'),
      ],
      '#default_value' => $config->get('separator'),
    ];
    $form['date_ap_style_settings']['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => ['' => $this->t('- Default site/user time zone -')] + system_time_zones(FALSE),
      '#size' => 1,
      '#default_value' => $config->get('timezone'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('date_ap_style.dateapstylesettings')
      ->set('date_ap_style_settings', $form_state->getValue('date_ap_style_settings'))
      ->set('always_display_year', $form_state->getValue('always_display_year'))
      ->set('use_today', $form_state->getValue('use_today'))
      ->set('cap_today', $form_state->getValue('cap_today'))
      ->set('display_day', $form_state->getValue('display_day'))
      ->set('display_time', $form_state->getValue('display_time'))
      ->set('time_before_date', $form_state->getValue('time_before_date'))
      ->set('use_all_day', $form_state->getValue('use_all_day'))
      ->set('display_noon_and_midnight', $form_state->getValue('display_noon_and_midnight'))
      ->set('capitalize_noon_and_midnight', $form_state->getValue('capitalize_noon_and_midnight'))
      ->set('timezone', $form_state->getValue('timezone'))
      ->save();
  }

}
