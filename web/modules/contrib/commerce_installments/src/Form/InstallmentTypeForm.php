<?php

namespace Drupal\commerce_installments\Form;

use Drupal\commerce_installments\Entity\InstallmentType;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class InstallmentTypeForm.
 */
class InstallmentTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_installments\Entity\InstallmentTypeInterface $installmentType */
    $installmentType = $this->entity;
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $workflows = $workflow_manager->getGroupedLabels('installment');

    $installment_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $installment_type->label(),
      '#description' => $this->t("Label for the Installment type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $installment_type->id(),
      '#machine_name' => [
        'exists' => InstallmentType::class . '::load',
      ],
      '#disabled' => !$installment_type->isNew(),
    ];

    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflows,
      '#default_value' => $installmentType->getWorkflowId(),
      '#description' => $this->t('Used by all instalments of this type.'),
    ];

    /* You will need additional form elements for your custom properties. */

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
    // Verify "Pay" transition.
    if (!$workflow->getTransition('pay')) {
      $form_state->setError($form['workflow'], $this->t('The @workflow workflow does not have a "Pay" transition.', [
        '@workflow' => $workflow->getLabel(),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_installments\Entity\InstallmentTypeInterface $installment_type */
    $installment_type = $this->entity;
    $status = $installment_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Installment type.', [
          '%label' => $installment_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Installment type.', [
          '%label' => $installment_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($installment_type->toUrl('collection'));
  }

}
