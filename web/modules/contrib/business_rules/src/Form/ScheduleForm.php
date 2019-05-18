<?php

namespace Drupal\business_rules\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Schedule edit forms.
 *
 * @ingroup business_rules
 */
class ScheduleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\business_rules\Entity\Schedule */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Create new revision'),
        '#default_value' => TRUE,
        '#weight'        => 10,
        '#required'      => TRUE,
      ];
    }

    $entity = $this->entity;

    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#default_value' => $entity->getName(),
      '#required'      => TRUE,
    ];

    $form['status'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Executed'),
      '#default_value' => $entity->isExecuted(),
    ];

    $scheduled_time    = $entity->isNew() ? '' : DrupalDateTime::createFromTimestamp($entity->getScheduled());
    $form['scheduled'] = [
      '#type'          => 'datetime',
      '#title'         => $this->t('Scheduled'),
      '#default_value' => $scheduled_time,
      '#required'      => TRUE,
    ];

    // Ask for the action to execute.
    $form['triggered_by'] = [
      '#type'               => 'entity_autocomplete',
      '#title'              => $this->t('Triggered by'),
      '#description'        => $this->t('Action that has supposed triggered this schedule.'),
      '#target_type'        => 'business_rules_action',
      '#selection_handler'  => 'default:business_rules_item_by_field',
      '#selection_settings' => [
        'filter' => ['type' => 'schedule_a_task'],
      ],
      '#default_value' => $entity->getTriggeredBy(),
      '#required'      => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\Entity\Schedule $entity */
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $entity->setScheduled($form_state->getValue('scheduled')->getTimestamp());
    $form_state->unsetValue('scheduled');
    $status = parent::save($form, $form_state);

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
    $form_state->setRedirect('entity.business_rules_schedule.collection');
  }

}
