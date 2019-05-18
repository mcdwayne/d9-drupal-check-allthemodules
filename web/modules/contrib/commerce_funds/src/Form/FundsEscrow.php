<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\commerce_funds\Entity\Transaction;

/**
 * Form to transfer create an escrow to another user account.
 */
class FundsEscrow extends ConfigFormBase {

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
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_escrow_payment';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.escrow',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currencyCodes = [];
    foreach ($currencies as $currency) {
      $currency_code = $currency->getCurrencyCode();
      $currencyCodes[$currency_code] = $currency_code;
    }
    $fees_description = \Drupal::service('commerce_funds.fees_manager')->printTransactionFees('transfer');

    $form['amount'] = [
      '#type' => 'number',
      '#min' => 0.0,
      '#title' => $this->t('Escrow Amount'),
      '#description' => $fees_description,
      '#step' => 0.01,
      '#default_value' => 0.0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Currency'),
      '#description' => $this->t('Select the currency you want to transfer.'),
      '#options' => $currencyCodes,
    ];

    $form['username'] = [
      '#id' => 'commerce-funds-escrow-to',
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
    $amount = $form_state->getValue('amount');
    $currency = $form_state->getValue('currency');
    $fee_applied = \Drupal::service('commerce_funds.fees_manager')->calculateTransactionFee($amount, $currency, 'transfer');

    $issuer = $this->currentUser;
    $issuer_balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($issuer->getAccount(), $currency);
    $currency_balance = isset($issuer_balance[$currency]) ? $issuer_balance[$currency] : 0;
    // Error if the user doesn't have enought money to cover the escrow + fee.
    if ($currency_balance < $fee_applied['net_amount']) {
      if (!$fee_applied['fee']) {
        $form_state->setErrorByName('amount', $this->t("You don't have enough funds to cover this escrow."));
      }
      if ($fee_applied['fee']) {
        $form_state->setErrorByName('amount', $this->t("You don't have enough funds to cover this escrow.<br>
        The commission applied is %commission (@currency).", [
          '%commission' => $fee_applied['fee'],
          '@currency' => $currency,
        ]));
      }
    }

    // Error if user try to make an escrow to itself.
    $recipient = $this->entityTypeManager->getStorage('user')->load($form_state->getValue('username'));
    if ($issuer->id() == $recipient->id()) {
      $form_state->setErrorByName('username', $this->t("Operation impossible. You can't transfer money to yourself."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $currency = $form_state->getValue('currency');
    $fee_applied = \Drupal::service('commerce_funds.fees_manager')->calculateTransactionFee($amount, $currency, 'transfer');

    $issuer = $this->currentUser;
    $recipient = $this->entityTypeManager->getStorage('user')->load($form_state->getValue('username'));

    $transaction = Transaction::create([
      'issuer' => $issuer->id(),
      'recipient' => $recipient->id(),
      'type' => 'escrow',
      'method' => 'internal',
      'brut_amount' => $amount,
      'net_amount' => $fee_applied['net_amount'],
      'fee' => $fee_applied['fee'],
      'currency' => $currency,
      'status' => 'Pending',
      'notes' => $form_state->getValue('notes'),
    ]);
    $transaction->save();

    \Drupal::service('commerce_funds.transaction_manager')->performTransaction($transaction);

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
    $mailManager->mail('commerce_funds', 'commerce_funds_transaction', $recipient->getEmail(), $langcode, $params, NULL, TRUE);

    // Set a confirmation message to user.
    if (!$fee_applied['fee']) {
      $no_fee_msg = $this->t('Escrow payment of @amount @currency successfully created to @user.', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@user' => $recipient->getAccountName(),
      ]);
      $this->messenger->addMessage($no_fee_msg, 'status');
    }
    if ($fee_applied['fee']) {
      $fee_msg = $this->t('Escrow payment of @amount @currency successfully created to @user with a commission of %commission @currency.', [
        '@amount' => $amount,
        '@currency' => $currency,
        '@user' => $recipient->getAccountName(),
        '%commission' => $fee_applied['fee'],
      ]);
      $this->messenger->addMessage($fee_msg, 'status');
    }
  }

}
