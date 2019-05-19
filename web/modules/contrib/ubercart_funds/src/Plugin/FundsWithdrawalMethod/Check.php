<?php

namespace Drupal\ubercart_funds\Plugin\FundsWithdrawalMethod;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserDataInterface;

/**
 * Provides check withdrawal method.
 *
 * @WithdrawalMethod(
 *   id = "check",
 *   name = @Translation("Check"),
 * )
 */
class Check extends ConfigFormBase {

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
    return 'uc_funds_withdrawal_check';
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
    $check_user_data = $this->userData->get('ubercart_funds', $uid, 'check');

    $form['check_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#description' => $this->t('Full Name to write the Check to'),
      '#default_value' => $check_user_data ? $check_user_data['check_name'] : '',
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['check_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#description' => $this->t('Detailed address to send the check to'),
      '#default_value' => $check_user_data ? $check_user_data['check_address'] : '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['check_address2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address 2'),
      '#description' => $this->t('Detailed address to send the check to'),
      '#default_value' => $check_user_data ? $check_user_data['check_address2'] : '',
      '#size' => 60,
      '#maxlength' => 128,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $uid = $this->account->id();
    $this->userData->set('ubercart_funds', $uid, 'check', $values);

    drupal_set_message($this->t('Withdrawal method successfully updated.'), 'status');
  }

}
