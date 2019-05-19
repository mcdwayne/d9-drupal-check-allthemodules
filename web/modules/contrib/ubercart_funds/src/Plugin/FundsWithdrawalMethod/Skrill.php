<?php

namespace Drupal\ubercart_funds\Plugin\FundsWithdrawalMethod;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserDataInterface;

/**
 * Provides skrill withdrawal method.
 *
 * @WithdrawalMethod(
 *   id = "skrill",
 *   name = @Translation("Skrill"),
 * )
 */
class Skrill extends ConfigFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Session\AccountInterface
   * @var \Drupal\user\UserDataInterface
   */
  protected $account;
  protected $userData;

  /**
   * Class constructor.
   */
  public function __construct(AccountInterface $account, UserDataInterface $user_data) {
    $this->account = $account;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('user.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_funds_withdrawal_skrill';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'uc_funds.withdrawal_methods',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = $this->account->id();
    $skrill_user_data = $this->userData->get('ubercart_funds', $uid, 'skrill');

    $form['skrill_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Skrill Email'),
      '#description' => $this->t('Withdrawals using Skrill will be sent to this email'),
      '#default_value' => $skrill_user_data ? $skrill_user_data['skrill_email'] : '',
      '#size' => 40,
      '#maxlength' => 64,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save informations'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $uid = $this->account->id();
    $this->userData->set('ubercart_funds', $uid, 'skrill', $values);

    drupal_set_message($this->t('Withdrawal method successfully updated.'), 'status');
  }

}
