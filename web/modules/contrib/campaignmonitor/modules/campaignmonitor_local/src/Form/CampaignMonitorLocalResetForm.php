<?php

namespace Drupal\campaignmonitor_local\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * .
 */
class CampaignMonitorLocalResetForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'campaignmonitor_local_reset_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $text = 'Submitting this form will create the subscriptions queue ready for processing.';
    $text .= '  Run this first before processing the queue.';
    $form['help'] = [
      '#type' => 'markup',
      '#markup' => $this->t($text),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create queue'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update list data.
    campaignmonitor_local_insert_list_data();
    // Queue all subscriptions.
    campaignmonitor_local_queue_subscriptions();
  }

}
