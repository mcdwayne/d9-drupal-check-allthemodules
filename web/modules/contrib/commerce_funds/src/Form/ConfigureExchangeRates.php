<?php

namespace Drupal\commerce_funds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure the exchange rates.
 */
class ConfigureExchangeRates extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, ClientInterface $http_client, LoggerInterface $logger, QueueFactory $queue_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('logger.channel.form'),
      $container->get('queue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_funds_configure_exchange_rates';
  }

  /**
   * {@inheritdoc}
   *
   * Https://www.drupal.org/docs/8/api/form-api/configformbase-with-simple-configuration-api.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_funds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_funds.settings');

    $store = $this->entityTypeManager->getStorage('commerce_store')->loadDefault();
    // If no store set a message.
    if (!$store) {
      $message = $this->t('You haven\'t configured a store yet. Please <a href="@url">configure one</a> before.', [
        '@url' => Url::fromRoute('entity.commerce_store.collection')->toString(),
      ]);
      $this->messenger->addError($message);
      return $form;
    }

    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    // If just one currency set a message.
    if (count($currencies) == 1) {
      $message = $this->t('You just have one currency enabled on your store. <a href="@url">Add more</a> to provide currency conversion.', [
        '@url' => Url::fromRoute('entity.commerce_currency.collection')->toString(),
      ]);
      $this->messenger->addError($message);
      return $form;
    }

    $form['transferwise'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Transferwise'),
      '#collapsible' => FALSE,
    ];

    $form['transferwise']['use_transferwise'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use transferwise to automatically manage the rates?'),
      '#default_value' => $config->get('transferwise')['use_transferwise'],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => 'transferwise-token',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    $form['transferwise']['ajax_container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'transferwise-token'],
    ];

    if (isset($form_state->getUserInput()['use_transferwise'])) {
      $form['transferwise']['ajax_container']['token'] = [
        '#title' => $this->t('Enter a sandbox API transferwise token.'),
        '#type' => 'textfield',
        '#size' => '60',
        '#description' => $this->t('Don\'t know how to generate a token? Visit <a href="@token-url" target="_blank">here</a>. Rates are updated on <a href="@cron-url">cron</a> run.<br>
        Register a sandbox account <a href="@sandbox-account">here</a> to generate the token, live API tokens are disallowed.', [
          '@url' => 'https://transferwise.com/help/article/2958908/transferwise-for-business/whats-a-personal-api-token-and-how-do-i-get-one',
          '@token-url' => 'https://sandbox.transferwise.tech/register#/',
          '@cron-url' => Url::fromRoute('system.cron_settings')->toString(),
        ]),
        '#required' => TRUE,
        '#attributes' => [
          'id' => ['token-output'],
        ],
      ];
    }
    elseif ($config->get('transferwise')['use_transferwise'] && !$form_state->getUserInput()) {
      $form['transferwise']['ajax_container']['token'] = [
        '#markup' => $this->t('Your token is hidden for security reasons.'),
      ];
    }

    $form['exchange_rates'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exchange rates'),
      '#collapsible' => TRUE,
    ];

    $exchange_rates = $config->get('exchange_rates') ?: [];

    foreach ($currencies as $currency_left) {
      $currency_code_left = $currency_left->getCurrencyCode();
      foreach ($currencies as $currency_right) {
        $currency_code_right = $currency_right->getCurrencyCode();
        if ($currency_code_left !== $currency_code_right) {
          $form['exchange_rates'][$currency_code_left . '_' . $currency_code_right] = [
            '#type' => 'number',
            '#min' => 0,
            '#title' => $this->t('@currency_left to @currency_right (%)', [
              '@currency_left' => $currency_code_left,
              '@currency_right' => $currency_code_right,
            ]),
            '#default_value' => $exchange_rates ? $exchange_rates[$currency_code_left . '_' . $currency_code_right] : 0,
            '#step' => 'any',
            '#size' => 10,
            '#maxlength' => 10,
            '#required' => TRUE,
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    if ($form_state->isSubmitted() && isset($values['token'])) {
      $token = $values['token'] ?: $this->config('commerce_funds.settings')->get('transferwise')['token'];
      if ($values['use_transferwise'] && !$this->validateTransferwiseToken($token)) {
        $form_state->setErrorByName('token', $this->t('Authentication failed. Please verify your token or make sure you are using the sandbox API. No live API tokens are allowed.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $this->config('commerce_funds.settings')
      ->set('transferwise', [
        'use_transferwise' => $values['use_transferwise'],
        'token' => isset($values['token']) ? $values['token'] : '',
      ])
      ->save();

    if ($values['use_transferwise']) {
      /** @var QueueInterface $queue */
      $queue = $this->queueFactory->get('commerce_funds_transferwise_rates');
      $queue->createQueue();
      $queue->createItem($values['token']);
    }
    else {
      /** @var QueueInterface $queue */
      $queue = $this->queueFactory->get('commerce_funds_transferwise_rates');
      $queue->deleteQueue();
    }

    unset($values['use_transferwise'], $values['token']);

    $this->config('commerce_funds.settings')
      ->set('exchange_rates', $values)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form['transferwise']['ajax_container'];
  }

  /**
   * Validate transferwise token.
   *
   * Send a GET request to fetch transferwise profile.
   * If a profile is returned, token is valid.
   *
   * @var string $token
   *  The transferwise token.
   *
   * @return bool
   *   True if token is valid.
   */
  protected function validateTransferwiseToken($token) {
    try {
      $response = $this->httpClient->request('GET', 'https://api.sandbox.transferwise.tech/v1/profiles', [
        'headers' => [
          'authorization' => 'Bearer ' . $token,
        ],
      ]);
    }
    catch (Exception $e) {
      $this->logger->error($e->getMessage());
    }
    finally {
      if (!isset($response)) {
        return FALSE;
      }
      elseif ($response->getStatusCode() == "200") {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

}
