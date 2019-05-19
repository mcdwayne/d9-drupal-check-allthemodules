<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\UserDataInterface;
use Drupal\ubercart_funds\Entity\Transaction;
use Drupal\ubercart_funds\WithdrawalMethodManager;

/**
 * Form to withdraw mmoney on user account.
 */
class UserFundsWithdraw extends ConfigFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Session\AccountProxy
   * @var \Drupal\user\UserDataInterface $userData
   * @var \Drupal\ubercart_funds\WithdrawalMethodManager $withdrawalMethodManager
   */
  protected $currentUser;
  protected $userData;
  protected $withdrawalMethodManager;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, UserDataInterface $user_data, WithdrawalMethodManager $withdrawal_method_manager) {
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->withdrawalMethodManager = $withdrawal_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('user.data'),
      $container->get('plugin.manager.withdrawal_method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_funds_withdraw';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'uc_funds.withdraw',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currency = $this->config('uc_store.settings')->get('currency.code');

    $methods = $this->withdrawalMethodManager->getEnabledWithdrawalMethods();
    foreach ($methods as $key => $method) {
      $fee = \Drupal::service('ubercart_funds.fees_manager')->printMethodFee('withdraw_' . $method['id']);
      $readable_methods[$key] = $method['name']->render() . ' ' . $fee;
    }
    $form['amount'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Amount to withdraw (@currency)', [
        '@currency' => $currency,
      ]
      ),
      '#description' => $this->t('Enter the amount you want to withdraw.'),
      '#default_value' => 0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['methods'] = [
      '#type' => 'radios',
      '#options' => $readable_methods,
      '#title' => $this->t('Select your prefered Withdrawal method'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Request'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Prepares needed variables.
    $method = $form_state->getValue('methods');
    $amount = $form_state->getValue('amount');
    $currency = $this->config('uc_store.settings')->get('currency.code');
    $fee = \Drupal::service('ubercart_funds.fees_manager')->calculateTransactionFee('withdraw_' . $method, $amount);

    $issuer_data = $this->userData->get('ubercart_funds', $this->currentUser->id(), $method);

    // Get issuer balance.
    $balance = \Drupal::service('ubercart_funds.transaction_manager')->loadAccountBalance($this->currentUser);

    if (intval($amount * 100) > $balance) {
      $form_state->setErrorByName('amount', $this->t("Your available balance is @balance @currency.", [
        '@balance' => $balance / 100,
        '@currency' => $currency,
      ]));
    }

    if ($fee['net_amount'] > $balance) {
      $form_state->setErrorByName('amount', $this->t('You cannot withdraw @amount @currency using this payment method. Commission is @fee @currency.', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@fee' => $fee['fee'] / 100,
      ]));
    }

    if (!$issuer_data) {
      $form_state->setErrorByName('methods', $this->t('Please <a href="@enter_details_link">enter details</a> for this withdrawal method first.', [
        '@enter_details_link' => Url::fromRoute('uc_funds.withdrawal_methods')->toString() . '/' . $method,
      ]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Prepares needed variables.
    $method = $form_state->getValue('methods');
    $amount = $form_state->getValue('amount');
    $currency = $this->config('uc_store.settings')->get('currency.code');
    $fee = \Drupal::service('ubercart_funds.fees_manager')->calculateTransactionFee('withdraw_' . $method, $amount);

    $transaction = Transaction::create([
      'issuer' => $this->currentUser->id(),
      'recipient' => $this->currentUser->id(),
      'type' => 'withdrawal_request',
      'method' => $method,
      'brut_amount' => intval($amount * 100),
      'net_amount' => $fee['net_amount'],
      'fee' => $fee['fee'],
      'currency' => $currency,
      'status' => 'Pending approval',
    ]);
    $transaction->save();

    // Set a confirmation message to user.
    drupal_set_message($this->t('Your Withdrawal Request has been sent and will be processed in due order.'));
    if ($fee['fee']) {
      drupal_set_message($this->t('An extra commission of @fee @currency will be apllied to your withraw.', [
        '@fee' => $fee['fee'] / 100,
        '@currency' => $currency,
      ]), 'status');
    }
  }

}
