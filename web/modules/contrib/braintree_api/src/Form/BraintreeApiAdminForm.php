<?php

namespace Drupal\braintree_api\Form;

use Braintree\Exception\Authentication;
use Braintree\Exception\Configuration;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;

/**
 * Class BraintreeApiAdminForm.
 */
class BraintreeApiAdminForm extends ConfigFormBase {

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApi;

  /**
   * The Braintree API Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BraintreeApiAdminForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, BraintreeApiService $braintree_api, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->logger = $logger;
    $this->braintreeApi = $braintree_api;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('braintree_api.braintree_api'),
      $container->get('logger.channel.braintree_api'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'braintree_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'braintree_api_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('braintree_api.settings');

    $form['sandbox'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Credentials from your Braintree <a href="https://sandbox.braintreegateway.com">sandbox account</a>. See <a href="https://articles.braintreepayments.com/control-panel/important-gateway-credentials">Braintree docs</a> about where to find credentials.'),
      '#title' => $this->t('Sandbox'),
    ];

    $form['production'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Credentials from your Braintree <a href="https://www.braintreegateway.com">production account</a>. See <a href="https://articles.braintreepayments.com/control-panel/important-gateway-credentials">Braintree docs</a> about where to find credentials.'),
      '#title' => $this->t('Production'),
    ];

    foreach (['sandbox', 'production'] as $environment) {
      $form[$environment][$environment . '_merchant_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Merchant ID'),
        '#default_value' => $config->get($environment . '_merchant_id'),
      ];

      $form[$environment][$environment . '_public_key'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Public Key'),
        '#default_value' => $config->get($environment . '_public_key'),
      ];

      $form[$environment][$environment . '_private_key'] = [
        '#type' => 'key_select',
        '#title' => $this->t('Private Key'),
        '#default_value' => $config->get($environment . '_private_key'),
      ];
    }

    $form['environment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Environment'),
      '#options' => [
        'sandbox' => $this->t('Sandbox'),
        'production' => $this->t('Production'),
      ],
      '#default_value' => $config->get('environment'),
    ];

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Webhook URL'),
      '#default_value' => Url::fromRoute('braintree_api.webhook', [], ['absolute' => TRUE])->toString(),
      '#description' => $this->t('Add this path for the Destination URL when creating a Webhook in your Braintree account. See the <a href="@braintree-docs">Braintree docs</a> for more information. Consult documentation for the module using Braintree API to determine for which events to enable notifications.', [
        '@braintree-docs' => Url::fromUri('https://developers.braintreepayments.com/guides/webhooks/overview', ['attributes' => ['target' => '_blank']])->toString(),
      ]),
    ];

    $form['braintree_test'] = [
      '#type' => 'button',
      '#value' => $this->t('Test Braintree Connection'),
      '#ajax' => [
        'callback' => [$this, 'testBraintreeConnection'],
        'wrapper' => 'braintree-connect-results',
        'method' => 'append',
      ],
      '#suffix' => '<div id="braintree-connect-results"></div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to test the Braintree connection.
   */
  public function testBraintreeConnection() {
    $items = [];
    try {
      $gateway = $this->braintreeApi->getGateway();
      $merchantAccountIterator = $gateway->merchantAccount()->all();
      foreach ($merchantAccountIterator as $merchantAccount) {
        $items[] = $this->t('Merchant account ID: %account_id, status: %status, currency code: %currency',
          [
            '%account_id' => $merchantAccount->id,
            '%status' => $merchantAccount->status,
            '%currency' => $merchantAccount->currencyIsoCode,
          ]);
      }
    }
    catch (Authentication $e) {
      $message = 'A Braintree_Exception_Authentication error occurred. There is a problem with the API key configuration.';
      $this->logger->error($message);
      return ['#markup' => '<p>' . $this->t($message) . '</p>'];
    }
    catch (Configuration $e) {
      $message = 'A Braintree_Exception_Configuration error occurred. Likely some of the API configuration has not yet be set.';
      $this->logger->error($message);
      return ['#markup' => '<p>' . $this->t($message) . '</p>'];
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return ['#markup' => '<p>' . $e->getMessage() . '</p>'];
    }
    return [
      '#theme' => 'item_list',
      '#title' => $this->t('Success!'),
      '#items' => $items,
      '#list_type' => 'ul',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();

    foreach (['sandbox', 'production'] as $environment) {
      $this->config('braintree_api.settings')
        ->set($environment . '_merchant_id', $values[$environment . '_merchant_id'])
        ->set($environment . '_public_key', $values[$environment . '_public_key'])
        ->set($environment . '_private_key', $values[$environment . '_private_key']);
    }
    $this->config('braintree_api.settings')
      ->set('environment', $values['environment'])
      ->save();
  }

}
