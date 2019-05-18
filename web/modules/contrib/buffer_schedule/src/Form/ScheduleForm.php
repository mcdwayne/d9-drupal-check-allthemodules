<?php

namespace Drupal\buffer_schedule\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Schedule edit forms.
 *
 * @ingroup buffer_schedule
 */
class ScheduleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\buffer_schedule\Entity\Schedule */
    $form = parent::buildForm($form, $form_state);

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Settings'),
      '#attributes' => [
        'id' => 'schedule-settings-wrapper'
      ],
      '#tree' => TRUE
    ];

    $form['settings']['schedule_type'] = [
      '#type' => 'select',
      '#title' => t('Schedule Type'),
      '#description' => t('Determines how often a unpublished item in the schedule buffer will be published.'),
      '#options' => [
        'interval' => t('Interval'),
        'days_of_week' => t('Day of the Week')
      ],
      '#default_value' => isset($this->entity->get('settings')->getValue()[0]) ? $this->entity->get('settings')->getValue()[0]['schedule_type'] : 'interval',
      '#ajax' => [
        'event' => 'change',
        'callback' => '::onScheduleTypeChange',
        'wrapper' => 'schedule-settings-wrapper'
      ]
    ];
    $values = $form_state->getValues();
    $settings = $values['settings'];

    if (isset($values['settings'])) {
        $schedule_type = $values['settings']['schedule_type'];
    }
    else {
      $schedule_type = isset($this->entity->get('settings')->getValue()[0]) ? $this->entity->get('settings')->getValue()[0]['schedule_type'] : 'interval';
    }


    switch($schedule_type) {
        default:
        case 'interval':
          $form['settings']['interval_time'] = [
            '#title' => t('Interval'),
            '#type' => 'textfield',
            '#description' => t('Enter the amount of time you want between publishing content. Example: 1 month, 1 week, 1 day, or 1 hour, etc.'),
            '#default_value' => isset($values['settings']['interval_time']) ? $values['settings']['interval_time'] : isset($this->entity->get('settings')->getValue()[0]['interval_time']) ? $this->entity->get('settings')->getValue()[0]['interval_time'] : '1 day'
          ];
        break;
        case 'days_of_week':
          $form['settings']['day_of_week'] = [
            '#type' => 'checkboxes',
            '#title' => t('Set the Day(s)'),
            '#description' => t('Select the days of the week you wish to publish content.'),
            '#options' => [
              'monday' => t('Monday'),
              'tuesday' => t('Tuesday'),
              'wednesday' => t('Wednesday'),
              'thursday' => t('Thursday'),
              'friday' => t('Friday'),
              'saturday' => t('Saturday'),
              'sunday' => t('Sunday')
            ],
            '#default_value' => isset($values['settings']['day_of_week']) ? $values['settings']['day_of_week'] : isset($this->entity->get('settings')->getValue()[0]) ? $this->entity->get('settings')->getValue()[0]['day_of_week'] :  NULL
          ];
        break;
    }

    $form['settings']['publish_amount'] = [
      '#title' => t('Number to Publish'),
      '#type' => 'textfield',
      '#description' => t('Enter the amount of items to publish when the time has lapsed.'),
      '#default_value' => isset($values['settings']['publish_amount']) ? $values['settings']['publish_amount'] : isset($this->entity->get('settings')->getValue()[0]) ? $this->entity->get('settings')->getValue()[0]['publish_amount'] : '1'
    ];


    $entity = $this->entity;

    return $form;
  }

  public function onScheduleTypeChange($form, FormStateInterface &$form_state) {
    $form_state->setRebuild(TRUE);
    return $form['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    $entity->settings->value = [$form_state->getValues()['settings']];
    $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Schedule.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Schedule.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.schedule.canonical', ['schedule' => $entity->id()]);
  }

}
