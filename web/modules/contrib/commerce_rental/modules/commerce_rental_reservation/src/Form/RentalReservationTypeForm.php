<?php

namespace Drupal\commerce_rental_reservation\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

class RentalReservationTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_rental_reservation\Entity\RentalReservationTypeInterface $reservation_type */
    $reservation_type = $this->entity;
    $content_entity_id = $reservation_type->getEntityType()->getBundleOf();

    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflows = $workflow_manager->getGroupedLabels('commerce_rental_reservation');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $reservation_type->label(),
      '#description' => $this->t("Label for the %content_entity_id entity type (bundle).", ['%content_entity_id' => $content_entity_id]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $reservation_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_rental\Entity\RentalReservationType::load',
      ],
      '#disabled' => !$reservation_type->isNew(),
    ];

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $reservation_type->getWorkflowId(),
      '#description' => $this->t('Used by all rental reservations of this type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\state_machine\WorkflowManager $workflow_manager */
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
    $workflow = $workflow_manager->createInstance($form_state->getValue('workflow'));
    //TODO: Figure out what states/transitions should be required for this and set validation accordingly.
/*    // Verify "Place" transition.
    if (!$workflow->getTransition('place')) {
      $form_state->setError($form['workflow'], $this->t('The @workflow workflow does not have a "Place" transition.', [
        '@workflow' => $workflow->getLabel(),
      ]));
    }
    // Verify "draft" state.
    if (!$workflow->getState('draft')) {
      $form_state->setError($form['workflow'], $this->t('The @workflow workflow does not have a "Draft" state.', [
        '@workflow' => $workflow->getLabel(),
      ]));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $reservation_type = $this->entity;
    $status = $reservation_type->save();
    $message_params = [
      '%label' => $reservation_type->label(),
      '%content_entity_id' => $reservation_type->getEntityType()->getBundleOf(),
    ];

    // Provide a message for the user and redirect them back to the collection.
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label %content_entity_id entity type.', $message_params));
        break;

      default:
        drupal_set_message($this->t('Saved the %label %content_entity_id entity type.', $message_params));
    }

    $form_state->setRedirectUrl($reservation_type->toUrl('collection'));
  }
}