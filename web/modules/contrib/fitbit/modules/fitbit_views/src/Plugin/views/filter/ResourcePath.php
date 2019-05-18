<?php

namespace Drupal\fitbit_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Resource path plugin.
 *
 * @ViewsFilter("fitbit_resource_path")
 */
class ResourcePath extends FilterPluginBase  {
  protected $alwaysMultiple = TRUE;

  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['value'] = ['default' => 'activities/steps'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $options = [
      'Activity' => [
        'activities/calories' => $this->t('Calories'),
        'activities/caloriesBMR' => $this->t('Calories BMR'),
        'activities/steps' => $this->t('Steps'),
        'activities/distance' => $this->t('Distance'),
        'activities/floors' => $this->t('Floors'),
        'activities/elevation' => $this->t('Elevation'),
        'activities/minutesSedentary' => $this->t('Minutes sedentary'),
        'activities/minutesLightlyActive' => $this->t('Minutes lightly active'),
        'activities/minutesFairlyActive' => $this->t('Minutes fairly active'),
        'activities/minutesVeryActive' => $this->t('Minutes very active'),
        'activities/activityCalories' => $this->t('Active calories'),
      ],
      'Tracker Activity' => [
        'activities/tracker/calories' => $this->t('Calories'),
        'activities/tracker/steps' => $this->t('Steps'),
        'activities/tracker/distance' => $this->t('Distance'),
        'activities/tracker/floors' => $this->t('Floors'),
        'activities/tracker/elevation' => $this->t('Elevation'),
        'activities/tracker/minutesSedentary' => $this->t('Minutes sedentary'),
        'activities/tracker/minutesLightlyActive' => $this->t('Minutes lightly active'),
        'activities/tracker/minutesFairlyActive' => $this->t('Minutes fairly active'),
        'activities/tracker/minutesVeryActive' => $this->t('Minutes very active'),
        'activities/tracker/activityCalories' => $this->t('Activity calories'),
      ],
    ];

    $form['value'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Value'),
      '#default_value' => $this->value,
    ];
  }
}
