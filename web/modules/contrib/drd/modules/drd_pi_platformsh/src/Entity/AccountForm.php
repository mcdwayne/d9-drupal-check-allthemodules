<?php

namespace Drupal\drd_pi_platformsh\Entity;

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

    /** @var \Drupal\drd_pi_platformsh\Entity\AccountInterface $platformsh_account */
    $platformsh_account = $this->entity;
    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#default_value' => $platformsh_account->getApiToken(),
      '#description' => $this->t('Obtain the API token from your PlatformSH dashboard in the "Account Settings" section.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\drd_pi_platformsh\Entity\AccountInterface $platformsh_account */
    $platformsh_account = $this->entity;
    $platformsh_account->setApiToken($form_state->getValue('api_token'));
  }

}
