<?php

namespace Drupal\drd_pi_pantheon\Entity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd_pi\DrdPiAccountForm;

/**
 * Class AccountForm.
 */
class AccountForm extends DrdPiAccountForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\drd_pi_pantheon\Entity\AccountInterface $pantheon_account */
    $pantheon_account = $this->entity;
    $form['machine_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine token'),
      '#default_value' => $pantheon_account->getMachineToken(),
      '#description' => $this->t('Obtain the machine token from your Pantheon dashboard in the "Account" section.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\drd_pi_pantheon\Entity\AccountInterface $pantheon_account */
    $pantheon_account = $this->entity;
    $pantheon_account->setMachineToken($form_state->getValue('machine_token'));
  }

}
