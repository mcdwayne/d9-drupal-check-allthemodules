<?php

namespace Drupal\ubercart_funds\Plugin\FundsWithdrawalMethod;

use Drupal\Core\Locale\CountryManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserDataInterface;

/**
 * Provides bank account withdrawal method.
 *
 * @WithdrawalMethod(
 *   id = "bank-account",
 *   name = @Translation("Bank account"),
 * )
 */
class BankAccount extends ConfigFormBase {

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
    return 'uc_funds_withdrawal_bank_account';
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
    $bank_user_data = $this->userData->get('ubercart_funds', $uid, 'bank_account');
    $countries = CountryManager::getStandardList();

    $form['account_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of Account Holder'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['account_name'] : '',
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['account_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Account Number / IBAN'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['account_number'] : '',
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['bank_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank Name'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['bank_name'] : '',
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['bank_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Bank Country'),
      '#options' => $countries,
      '#default_value' => $bank_user_data ? $bank_user_data['bank_country'] : '',
      '#description' => '',
      '#required' => TRUE,
    ];

    $form['swift_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Swift Code'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['swift_code'] : '',
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['bank_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank Address'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['bank_address'] : '',
      '#size' => 40,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['bank_address2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank Address 2'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['bank_address2'] : '',
      '#size' => 40,
      '#maxlength' => 128,
    ];

    $form['bank_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank City'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['bank_city'] : '',
      '#size' => 20,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['bank_province'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank Province'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['bank_province'] : '',
      '#size' => 20,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['bank_postalcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bank Postal Code'),
      '#description' => '',
      '#default_value' => $bank_user_data ? $bank_user_data['bank_postalcode'] : '',
      '#size' => 20,
      '#maxlength' => 128,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @TODO
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $uid = $this->account->id();
    $this->userData->set('ubercart_funds', $uid, 'bank_account', $values);

    drupal_set_message($this->t('Withdrawal method successfully updated.'), 'status');
  }

}
