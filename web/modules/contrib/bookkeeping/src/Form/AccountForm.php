<?php

namespace Drupal\bookkeeping\Form;

use Drupal\bookkeeping\Entity\Account;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form for editing accounts.
 */
class AccountForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\bookkeeping\Entity\AccountInterface $account */
    $account = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account name'),
      '#maxlength' => 255,
      '#default_value' => $account->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $account->id(),
      '#machine_name' => [
        'exists' => Account::class . '::load',
      ],
      '#disabled' => !$account->isNew(),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Account type'),
      '#default_value' => $account->getType(),
      '#required' => TRUE,
      '#options' => Account::getTypeOptions(),
    ];

    $form['rollup'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Roll up transactions'),
      '#default_value' => $account->shouldRollup(),
      '#description' => $this->t('If enabled, exports will roll up transactions from a single day into a pair of Credit and Debit transactions.'),
    ];

    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account code'),
      '#default_value' => $account->getCode(),
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#default_value' => $account->getDepartment(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\bookkeeping\Entity\AccountInterface $bookkeeping_account */
    $bookkeeping_account = $this->entity;
    $status = $bookkeeping_account->save();

    $params = ['%label' => $bookkeeping_account->label()];
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addStatus($this->t('Created the %label account.', $params));
        break;

      default:
        $this->messenger()
          ->addStatus($this->t('Saved the %label account.', $params));
    }

    $form_state->setRedirectUrl($bookkeeping_account->toUrl('collection'));
  }

}
