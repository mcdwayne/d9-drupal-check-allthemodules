<?php

namespace Drupal\ubercart_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ubercart_funds\Entity\Transaction;

/**
 * Form to transfer create an escrow to another user account.
 */
class UserFundsEscrow extends ConfigFormBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Session\AccountProxy
   * @var \Drupal\user\UserStorageInterface $userStorage
   * @var \Drupal\Core\Mail\MailManagerInterface $mailManager
   */
  protected $currentUser;
  protected $userStorage;
  protected $mailManager;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, UserStorageInterface $user_storage, MailManagerInterface $mail_manager) {
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_funds_escrow_payment';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'uc_funds.escrow',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currency = $this->config('uc_store.settings')->get('currency.code');
    $fees_description = \Drupal::service('ubercart_funds.fees_manager')->printTransactionFees('escrow');

    $form['amount'] = [
      '#type' => 'number',
      '#min' => 0.0,
      '#title' => $this->t('Escrow Amount (@currency)',
        ['@currency' => $currency]
      ),
      '#description' => $fees_description,
      '#step' => 0.01,
      '#default_value' => 0.0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['username'] = [
      '#id' => 'uc-funds-escrow-to',
      '#title' => $this->t('Allocated To'),
      '#description' => $this->t('Please enter a username.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 128,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
    ];

    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#description' => $this->t('Eventually add a message to the recipient.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create escrow'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Prepares needed variables.
    $amount = $form_state->getValue('amount');
    $currency = $this->config('uc_store.settings')->get('currency.code');
    $fee = \Drupal::service('ubercart_funds.fees_manager')->calculateTransactionFee('escrow', $amount);

    // Get issuer balance.
    $balance = \Drupal::service('ubercart_funds.transaction_manager')->loadAccountBalance($this->currentUser);

    // Error if the user doesn't have enought money to cover the escrow + fee.
    if ($balance < $fee['net_amount']) {
      $form_state->setErrorByName('amount', $this->t("You don't have enough funds to cover this escrow.<br>
      The commission applied is %commission (@currency).",
      [
        '%commission' => $fee['fee'] / 100,
        '@currency' => $currency,
      ]));
    }

    // Error if user try to make an escrow to itself.
    $recipient = $this->userStorage->load($form_state->getValue('username'));
    if ($this->currentUser->id() == $recipient->id()) {
      $form_state->setErrorByName('username', $this->t("Operation impossible. You can't transfer money to yourself."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Prepares needed variables.
    $amount = $form_state->getValue('amount');
    $currency = $this->config('uc_store.settings')->get('currency.code');
    $fee = \Drupal::service('ubercart_funds.fees_manager')->calculateTransactionFee('escrow', $amount);

    $issuer = $this->currentUser;
    $recipient = $this->userStorage->load($form_state->getValue('username'));

    $transaction = Transaction::create([
      'issuer' => $issuer->id(),
      'recipient' => $recipient->id(),
      'type' => 'escrow',
      'method' => 'internal',
      'brut_amount' => intval($amount * 100),
      'net_amount' => $fee['net_amount'],
      'fee' => $fee['fee'],
      'currency' => $currency,
      'status' => 'Pending',
      'notes' => $form_state->getValue('notes'),
    ]);
    $transaction->save();

    // Perform transfer.
    \Drupal::service('ubercart_funds.transaction_manager')->performTransaction($transaction);

    // Send an HTML email to the recipient.
    // @TODO Create a rule to handle the messages,
    // when rules will have Typed data implemented.
    $mailManager = $this->mailManager;
    $langcode = $this->config('system.site')->get('langcode');

    $params = [
      'subject' => $this->t('New escrow payment!'),
      'title' => $this->t('Dear @recipient,', [
        '@recipient' => $recipient->getAccountName(),
      ]),
      'body' => $this->t('@issuer just created an escrow payment of @amount @currency which you are destinary of.', [
        '@issuer' => $issuer->getAccountName(),
        '@amount' => $amount,
        '@currency' => $currency,
      ]),
    ];
    $mailManager->mail('ubercart_funds', 'uc_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);

    // Set a confirmation message to user.
    if (!$fee['fee']) {
      drupal_set_message($this->t('Escrow payment of @amount @currency successfully created to @user.', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@user' => $recipient->getAccountName(),
      ]), 'status');
    }
    if ($fee['fee']) {
      drupal_set_message($this->t('Escrow payment of @amount @currency successfully created to @user with a commission of %commission @currency.',
      [
        '@amount' => $amount,
        '@currency' => $currency,
        '@user' => $recipient->getAccountName(),
        '%commission' => $fee['fee'] / 100,
      ]), 'status');
    }
  }

}
