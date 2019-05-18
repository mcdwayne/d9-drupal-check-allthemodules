<?php

namespace Drupal\commerce_rental_reservation\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

class RentalInstanceTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_rental_reservation\Entity\RentalInstanceTypeInterface $instance_type */
    $instance_type = $this->entity;
    $content_entity_id = $instance_type->getEntityType()->getBundleOf();

    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflows = $workflow_manager->getGroupedLabels('commerce_rental_instance');

    $selector_manager = \Drupal::service('plugin.manager.rental_instance_selector');
    $selectors = [];

    foreach ($selector_manager->getDefinitions() as $key => $value) {
      $selectors[$key] = $key;
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $instance_type->label(),
      '#description' => $this->t("Label for the %content_entity_id entity type (bundle).", ['%content_entity_id' => $content_entity_id]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $instance_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_rental\Entity\RentalInstanceType::load',
      ],
      '#disabled' => !$instance_type->isNew(),
    ];

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $instance_type->getWorkflowId(),
      '#description' => $this->t('Used by all rental instances of this type.'),
    ];

    $form['instanceSelector'] = [
      '#type' => 'select',
      '#title' => $this->t('Selector'),
      '#options' => $selectors,
      '#default_value' => $instance_type->getSelectorId(),
      '#description' => $this->t('The rental instance selector plugin that is used to automatically assign instances to order items'),
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
    $instance_type = $this->entity;
    $status = $instance_type->save();
    $message_params = [
      '%label' => $instance_type->label(),
      '%content_entity_id' => $instance_type->getEntityType()->getBundleOf(),
    ];

    // Provide a message for the user and redirect them back to the collection.
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label %content_entity_id entity type.', $message_params));
        break;

      default:
        drupal_set_message($this->t('Saved the %label %content_entity_id entity type.', $message_params));
    }

    $form_state->setRedirectUrl($instance_type->toUrl('collection'));
  }
}