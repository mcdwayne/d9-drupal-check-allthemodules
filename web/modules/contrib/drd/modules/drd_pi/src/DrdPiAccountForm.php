<?php

namespace Drupal\drd_pi;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DrdPiAccountForm.
 */
abstract class DrdPiAccountForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\drd_pi\DrdPiAccountInterface $account */
    $account = $this->entity;

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $account->status(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $account->label(),
      '#description' => $this->t('Label for the Account.'),
      '#required' => TRUE,
    ];

    $module = $account::getModuleName();
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $account->id(),
      '#machine_name' => [
        'exists' => "\Drupal\\$module\Entity\Account::load",
      ],
      '#disabled' => !$account->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label account.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label account.', [
          '%label' => $this->entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
