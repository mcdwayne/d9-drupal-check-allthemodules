<?php

namespace Drupal\happy_new_year\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines a form that configures forms module settings.
 */
class HnySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'happy_new_year_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'happy_new_year.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('happy_new_year.settings');

    $date_start = $config->get('happy_new_year_start');
    $date_end = $config->get('happy_new_year_end');

    if (empty($date_start)) {
      $date_start = new DrupalDateTime();
      $date_start->setDate(date('Y'), 12, 1);
    }
    else {
      $date_start = new DrupalDateTime($date_start);
    }

    if (empty($date_end)) {
      $date_end = new DrupalDateTime();
      $date_end->setDate(date('Y') + 1, 1, 15);
    }
    else {
      $date_end = new DrupalDateTime($date_end);
    }

    $form = [];
    $form['period'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Working period'),
      '#attached' => [
        'library' => [
          'happy_new_year/settings-form',
        ],
      ],
    ];
    $form['period']['happy_new_year_period'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable working period'),
      '#default_value' => $config->get('happy_new_year_period'),
      '#description' => $this->t('If the working period is not specified, the module will run all the time'),
    ];
    $form['period']['period_dates'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select start date and end date'),
      '#states' => [
        'visible' => [
          '#edit-happy-new-year-period' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['period']['period_dates']['happy_new_year_start'] = [
      '#type' => 'datelist',
      '#title' => $this->t('Start date'),
      '#description' => $this->t("Current year is used."),
      '#default_value' => $date_start,
      '#date_part_order' => ['day', 'month', 'year'],
    ];
    $form['period']['period_dates']['happy_new_year_end'] = [
      '#type' => 'datelist',
      '#title' => $this->t('End date'),
      '#description' => $this->t("Next year is used."),
      '#default_value' => $date_end,
      '#date_part_order' => ['day', 'month', 'year'],
    ];
    $form['snow_and_garland'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Garland and Snow'),
    ];
    $form['snow_and_garland']['happy_new_year_garland'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable garland'),
      '#default_value' => $config->get('happy_new_year_garland'),
    ];
    $form['snow_and_garland']['garland_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Garland settings'),
      '#states' => [
        'visible' => [
          '#edit-happy-new-year-garland' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['snow_and_garland']['garland_settings']['happy_new_year_garland_fixed'] = [
      '#type' => 'checkbox',
      '#title' => t('Top-Fixed garland'),
      '#default_value' => $config->get('happy_new_year_garland_fixed'),
    ];
    $form['snow_and_garland']['happy_new_year_snow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable snow'),
      '#default_value' => $config->get('happy_new_year_snow'),
    ];
    $form['snow_and_garland']['snow_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Snow settings'),
      '#states' => [
        'visible' => [
          '#edit-happy-new-year-snow' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['snow_and_garland']['snow_settings']['happy_new_year_snowcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Snow color'),
      '#suffix' => '<div id="color-picker"></div>',
      '#default_value' => $config->get('happy_new_year_snowcolor') ?: '#FFFFFF',
      '#maxlength' => 7,
      '#size' => 7,
      '#attached' => [
        'library' => [
          'core/jquery.farbtastic',
          'happy_new_year/colorpicker',
        ],
      ],
    ];
    $form['happy_new_year_minified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use minified libraries if it possible'),
      '#default_value' => $config->get('happy_new_year_minified'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('happy_new_year.settings')
      ->set('happy_new_year_period', $values['happy_new_year_period'])
      ->set('happy_new_year_start', $values['happy_new_year_start']->__toString())
      ->set('happy_new_year_end', $values['happy_new_year_end']->__toString())
      ->set('happy_new_year_garland', $values['happy_new_year_garland'])
      ->set('happy_new_year_garland_fixed', $values['happy_new_year_garland_fixed'])
      ->set('happy_new_year_snow', $values['happy_new_year_snow'])
      ->set('happy_new_year_snowcolor', $values['happy_new_year_snowcolor'])
      ->set('happy_new_year_minified', $values['happy_new_year_minified'])
      ->save();

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
