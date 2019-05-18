<?php

namespace Drupal\loggable\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Provide a form for loggable filter entities.
 */
class LoggableFilterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $form['help'] = [
      '#type' => 'item',
      '#markup' => $this->t('Events that match the criteria below will be sent to Loggable.'),
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => !$entity->isNew() ? $entity->label() : NULL,
      '#description' => $this->t('The label for the filter.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\loggable\Entity\LoggableFilter::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['severity_levels'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Severity'),
      '#options' => [
        RfcLogLevel::DEBUG => $this->t('Debug'),
        RfcLogLevel::INFO => $this->t('Info'),
        RfcLogLevel::NOTICE => $this->t('Notice'),
        RfcLogLevel::WARNING => $this->t('Warning'),
        RfcLogLevel::ERROR => $this->t('Error'),
        RfcLogLevel::CRITICAL => $this->t('Critical'),
        RfcLogLevel::ALERT => $this->t('Alert'),
        RfcLogLevel::EMERGENCY => $this->t('Emergency'),
      ],
      '#required' => TRUE,
      '#default_value' => $entity->getSeverityLevels(),
      '#description' => $this->t('Select the severity levels.'),
    ];
    $form['types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Types'),
      '#required' => FALSE,
      '#default_value' => implode("\n", $entity->getTypes()),
      '#description' => $this->t('Optionally filter by the event type. You may enter an unlimited amount of types. Each type must go on a new line. Wildcard (*) characters are supported.'),
    ];
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->isEnabled(),
      '#description' => $this->t('If disabled, this filter will be ignored.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Store only checked levels.
    $levels = [];
    foreach ($entity->getSeverityLevels() as $key => $value) {
      // Since 0 is an option, we cannot use array_filter().
      // Checking if the value is a string indicates it was selected.
      if (is_string($value)) {
        $levels[] = $key;
      }
    }

    // Store the cleaned severity levels.
    $entity->setSeverityLevels($levels);

    // Reformat the types.
    $entity->setTypes(array_filter(array_map('trim', explode("\n", $entity->getTypes()))));

    // Save the entity.
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label filter.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label filter.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
