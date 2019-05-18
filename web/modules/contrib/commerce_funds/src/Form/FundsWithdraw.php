<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\user\UserDataInterface;
use Drupal\commerce_funds\Entity\Transaction;

/**
 * Form to withdraw mmoney on user account.
 */
class FundsWithdraw extends ConfigFormBase {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user data interface.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager, UserDataInterface $user_data, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->userData = $user_data;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('user.data'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_withdraw';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.withdraw',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $methods = $this->config('commerce_funds.settings')->get('withdrawal_methods');
    if (!$methods) {
      throw new NotFoundHttpException();
    }
    foreach ($methods['methods'] as $key => $method) {
      if (!is_numeric($method)) {
        $fee = \Drupal::service('commerce_funds.fees_manager')->printPaymentGatewayFees($key, $this->t('unit(s)'), 'withdraw')?:'';
        $enabled_method['methods'][$key] = $method . ' ' . $fee;
      }
    }

    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currencyCodes = [];
    foreach ($currencies as $currency) {
      $currency_code = $currency->getCurrencyCode();
      $currencyCodes[$currency_code] = $currency_code;
    }

    $form['amount'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Amount to withdraw'),
      '#description' => $this->t('Enter the amount you want to withdraw.'),
      '#default_value' => 0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the currency'),
      '#description' => $this->t('Select the currency you want to withdraw.'),
      '#options' => $currencyCodes,
    ];

    $form['methods'] = [
      '#type' => 'radios',
      '#options' => $enabled_method['methods'],
      '#title' => $this->t('Select your preferred withdrawal method.'),
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
    $method = $form_state->getValue('methods');
    $amount = $form_state->getValue('amount');
    $currency = $form_state->getValue('currency');
    $fee_applied = \Drupal::service('commerce_funds.fees_manager')->calculateTransactionFee($amount, $currency, 'withdraw');

    $issuer = $this->currentUser;
    $issuer_balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($issuer->getAccount(), $currency);
    $currency_balance = isset($issuer_balance[$currency]) ? $issuer_balance[$currency] : 0;
    $issuer_data = $this->userData->get('commerce_funds', $issuer->id(), $method);

    if ($amount > $currency_balance) {
      $form_state->setErrorByName('amount', $this->t("Your available balance is @balance @currency.", [
        '@balance' => $currency_balance,
        '@currency' => $currency,
      ]));
    }

    if ($fee_applied['net_amount'] > $currency_balance) {
      $form_state->setErrorByName('amount', $this->t('You cannot withdraw @amount @currency using this payment method. Commission is @fee @currency.', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@fee' => $fee_applied['fee'] / 100,
      ]));
    }

    if (!$issuer_data) {
      $form_state->setErrorByName('methods', $this->t('Please <a href="@enter_details_link">enter your details</a> for this withdrawal method first.', [
        '@enter_details_link' => Url::fromRoute('commerce_funds.withdrawal_methods')->toString() . '/' . $method,
      ]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $issuer = $this->currentUser;
    $method = $form_state->getValue('methods');
    $amount = $form_state->getValue('amount');
    $currency = $form_state->getValue('currency');
    $fee_applied = \Drupal::service('commerce_funds.fees_manager')->calculateTransactionFee($amount, $currency, 'withdraw_' . $method);

    $transaction = Transaction::create([
      'issuer' => $issuer->id(),
      'recipient' => $issuer->id(),
      'type' => 'withdrawal_request',
      'method' => $method,
      'brut_amount' => $amount,
      'net_amount' => $fee_applied['net_amount'],
      'fee' => $fee_applied['fee'],
      'currency' => $currency,
      'status' => 'Pending',
      'notes' => $form_state->getValue('notes'),
    ]);
    $transaction->save();

    // Set a confirmation message to user.
    $confirmation_msg = $this->t('Your Withdrawal Request has been sent and will be processed in due order.');
    $this->messenger->addMessage($confirmation_msg, 'status');
    if ($fee_applied['fee']) {
      $fee_msg = $this->t('An extra commission of @fee @currency will be apllied to your withraw.', [
        '@fee' => $fee_applied['fee'],
        '@currency' => $currency,
      ]);
      $this->messenger->addMessage($fee_msg, 'status');
    }
  }

}
