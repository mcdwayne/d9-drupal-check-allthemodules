<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\commerce_funds\Entity\Transaction;

/**
 * Form to convert currencies.
 */
class FundsConverter extends ConfigFormBase {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(AccountProxy $current_user, EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('current_user'),
    $container->get('entity_type.manager'),
    $container->get('config.factory'),
    $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_converter';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.converter',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(FALSE);
    $exchange_rates = \Drupal::config('commerce_funds.settings')->get('exchange_rates');
    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    $currencyCodes = [];
    foreach ($currencies as $currency) {
      $currency_code = $currency->getCurrencyCode();
      $currencyCodes[$currency_code] = $currency_code;
    }

    $form['currency_left'] = [
      '#type' => 'select',
      '#title' => $this->t('From'),
      '#description' => $this->t('The currency to convert.'),
      '#options' => $currencyCodes,
      '#ajax' => [
        'callback' => [get_class($this), 'printRate'],
        'event' => 'click',
        'wrapper' => 'exchange-rate',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Calculating rate...'),
        ],
      ],
    ];

    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('Amount to convert.'),
      '#min' => 0.0,
      '#step' => 0.01,
      '#default_value' => 0,
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'printRate'],
        'event' => 'end_typing',
        'wrapper' => 'exchange-rate',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Calculating rate...'),
        ],
      ],
      '#attributes' => [
        'class'      => [
          'delayed-input-submit',
        ],
        'data-delay' => '400',
      ],
    ];

    $form['currency_right'] = [
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#description' => $this->t('The to currency to convert into.'),
      '#options' => $currencyCodes,
      '#ajax' => [
        'callback' => [get_class($this), 'printRate'],
        'event' => 'click',
        'wrapper' => 'exchange-rate',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Calculating rate...'),
        ],
      ],
    ];

    $form['ajax_container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'exchange-rate'],
    ];

    $rate_description = '';

    if (!empty($form_state->getUserInput())) {
      if ($form_state->getUserInput()['currency_left'] != $form_state->getUserInput()['currency_right']) {
        $new_amount = \Drupal::service('commerce_funds.fees_manager')->printConvertedAmount($form_state->getUserInput()['amount'], $form_state->getUserInput()['currency_left'], $form_state->getUserInput()['currency_right']);
        $rate_description = $this->t('Conversion rate applied: @exchange-rate% <br> Amount after conversion: @new_amount', [
          '@exchange-rate' => $exchange_rates[$form_state->getUserInput()['currency_left'] . '_' . $form_state->getUserInput()['currency_right']]?:'1',
          '@new_amount' => $new_amount,
        ]);
      }
    }

    $form['ajax_container']['markup'] = [
      '#markup' => $rate_description?:$this->t('Conversion rate applied: 1%'),
      '#attributes' => [
        'id' => ['rate-output'],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Convert funds'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isSubmitted()) {
      $amount = $form_state->getValue('amount');
      $currency = $form_state->getValue('currency_left');

      $issuer = $this->currentUser;
      $issuer_balance = \Drupal::service('commerce_funds.transaction_manager')->loadAccountBalance($issuer->getAccount(), $currency);
      $currency_balance = isset($issuer_balance[$currency]) ? $issuer_balance[$currency] : 0;

      // Error if the user doesn't have enought money to cover the escrow + fee.
      if ($currency_balance < $amount) {
        $form_state->setErrorByName('amount', $this->t('You don\'t have enough funds. Please <a href="@url">make a deposit</a> first.', [
          '@url' => Url::fromRoute('commerce_funds.deposit')->toString(),
        ]));
      }

      // You can't convert a currency into intself.
      if ($currency === $form_state->getValue('currency_right')) {
        $form_state->setErrorByName('currency_right', $this->t('Operation impossible. Please chose another currency.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $amount = $form_state->getValue('amount');
    $exchange_rates = \Drupal::config('commerce_funds.settings')->get('exchange_rates');
    $conversion = \Drupal::service('commerce_funds.fees_manager')->convertCurrencyAmount($amount, $form_state->getValue('currency_left'), $form_state->getValue('currency_right'));

    $transaction = Transaction::create([
      'issuer' => $this->currentUser->id(),
      'recipient' => $this->currentUser->id(),
      'type' => 'conversion',
      'method' => 'internal',
      'brut_amount' => $amount,
      'net_amount' => $conversion['new_amount'],
      'fee' => $conversion['rate'],
      'from_currency' => $form_state->getValue('currency_left'),
      'currency' => $form_state->getValue('currency_right'),
      'status' => 'Completed',
      'notes' => $this->t('@amount @currency_left converted into @new_amount @currency_right using @rate% rate.', [
        '@amount' => $amount,
        '@currency_left' => $form_state->getValue('currency_left'),
        '@new_amount' => $conversion['new_amount'],
        '@currency_right' => $form_state->getValue('currency_right'),
        '@rate' => $conversion['rate'],
      ]),
    ]);
    $transaction->save();

    \Drupal::service('commerce_funds.transaction_manager')->performTransaction($transaction);

  }

  /**
   * Ajax callback.
   */
  public static function printRate($form, $form_state) {
    return $form['ajax_container'];
  }

}
