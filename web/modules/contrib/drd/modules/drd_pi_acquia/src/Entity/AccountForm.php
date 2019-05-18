<?php

namespace Drupal\drd_pi_acquia\Entity;

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

    /** @var \Drupal\drd_pi_acquia\Entity\AccountInterface $acquia_account */
    $acquia_account = $this->entity;

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#default_value' => $acquia_account->getEmail(),
      '#required' => TRUE,
    ];
    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#default_value' => $acquia_account->getPrivateKey(),
      '#description' => $this->t('Obtain both values from your Acquia dashboard at https://accounts.acquia.com/account/security.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\drd_pi_acquia\Entity\AccountInterface $acquia_account */
    $acquia_account = $this->entity;
    $acquia_account->setPrivateKey($form_state->getValue('private_key'));
  }

}
