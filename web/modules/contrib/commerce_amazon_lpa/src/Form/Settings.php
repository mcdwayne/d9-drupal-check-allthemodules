<?php

namespace Drupal\commerce_amazon_lpa\Form;

use Drupal\commerce\EntityHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Amazon settings form.
 */
class Settings extends ConfigFormBase {

  protected $storeStorage;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get editable config names.
   */
  protected function getEditableConfigNames() {
    return ['commerce_amazon_lpa.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_amazon_lpa_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('commerce_amazon_lpa.settings');

    $form['configuration'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Configuration'),
    ];
    $form['configuration']['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Environment'),
      '#options' => [
        'test' => $this->t('Sandbox'),
        'live' => $this->t('Live'),
      ],
      '#default_value' => $config->get('mode'),
    ];
    $form['configuration']['operation_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Operation mode'),
      '#description' => $this->t('Provide Amazon Pay and Login with Amazon or just Amazon Pay. Using Login with Amazon links Drupal user accounts with the Amazon account'),
      '#options' => [
        'pay_lwa' => $this->t('Amazon Pay and Login with Amazon'),
        'pay' => $this->t('Amazon Pay only'),
      ],
      '#default_value' => $config->get('operation_mode'),
    ];
    $form['configuration']['role_access'] = [
      '#type' => 'radios',
      '#title' => $this->t('Hidden button mode'),
      '#description' => $this->t('This allows you to keep the integration active, but only available to select roles.'),
      '#options' => ['' => 'Disabled'] + EntityHelper::extractLabels(user_roles(TRUE)),
      '#default_value' => ($config->get('role_access')) ? $config->get('role_access') : '',
    ];
    $form['configuration']['use_popup'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use a redirect or popup for login'),
      '#options' => [
        TRUE => $this->t('Popup'),
        FALSE => $this->t('Redirect'),
      ],
      '#default_value' => (!is_null($config->get('use_popup'))) ? $config->get('use_popup') : TRUE,
    ];

    $form['payment'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Payment settings'),
    ];
    $form['payment']['authorization_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Authorization mode'),
      '#options' => [
        'sync' => $this->t('Automatic synchronous authorization in frontend'),
        'async' => $this->t('Automatic non-synchronous after order is placed'),
        'manual' => $this->t('Manual non-synchronous authorization through order management.'),
      ],
      '#default_value' => ($config->get('authorization_mode')) ? $config->get('authorization_mode') : 'sync',
    ];
    $form['payment']['capture_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Capture mode'),
      '#options' => [
        'auth_capture' => $this->t('Immediate capture on successful authorization'),
        'shipment_capture' => $this->t('Capture on shipment'),
        'manual' => $this->t('Manual capture through order management'),
      ],
      '#default_value' => ($config->get('capture_mode')) ? $config->get('capture_mode') : 'shipment_capture',
      '#description' => $this->t('Whitelisting with Amazon Pay is required for "Immediate capture on successful authorization"'),
    ];
    $form['payment']['auth_statement'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authorization statement'),
      '#default_value' => $config->get('auth_statement') ? $config->get('auth_statement') : '',
      '#description' => $this->t('This will appear at the bottom of your Amazon order notifications.'),
      '#states' => [
        'visible' => [
          ':input[name="capture_mode"]' => ['value' => 'auth_capture'],
        ],
      ],
    ];

    $form['appearance'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Appearance'),
    ];
    $buttons = $config->get('buttons') ?: [
      'pay' => [
        'size' => 'medium',
        'style' => 'Gold',
      ],
      'login' => [
        'size' => 'medium',
        'style' => 'Gold',
      ],
    ];
    $form['appearance']['buttons'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Buttons'),
    ];
    $form['appearance']['buttons']['pay'] = [
      '#type' => 'container',
    ];
    $form['appearance']['buttons']['pay']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Amazon Pay button size'),
      '#options' => [
        'small' => t('Small'),
        'medium' => t('Medium'),
        'large' => t('Large'),
        'x-large' => t('Extra large'),
      ],
      '#default_value' => $buttons['pay']['size'],
    ];
    $form['appearance']['buttons']['pay']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Amazon Pay button style'),
      '#options' => [
        'Gold' => t('Gold'),
        'LightGray' => t('Light gray'),
        'DarkGray' => t('Dark gray'),
      ],
      '#default_value' => $buttons['pay']['style'],
    ];
    $form['appearance']['buttons']['login'] = [
      '#type' => 'container',
    ];
    $form['appearance']['buttons']['login']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Login with Amazon button size'),
      '#options' => [
        'small' => t('Small'),
        'medium' => t('Medium'),
        'large' => t('Large'),
        'x-large' => t('Extra large'),
      ],
      '#default_value' => $buttons['login']['size'],
    ];

    $form['appearance']['buttons']['login']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Login with Amazon button style'),
      '#options' => [
        'Gold' => t('Gold'),
        'LightGray' => t('Light gray'),
        'DarkGray' => t('Dark gray'),
      ],
      '#default_value' => $buttons['login']['style'],
    ];

    $merchant_config = $config->get('merchant_information');
    $form['merchant_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Merchant account information'),
      '#open' => empty($merchant_config),
      '#tree' => TRUE,
    ];
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    foreach ($this->storeStorage->loadMultiple() as $store) {
      $uuid = $store->uuid();
      $supported_billing = $store->getBillingCountries();

      $form['merchant_information'][$uuid] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Merchant account settings for @store', ['@store' => $store->label()]),
        '#tree' => TRUE,
      ];

      if (empty(array_intersect($supported_billing, ['DE', 'GB', 'US']))) {
        $form['merchant_information'][$uuid]['#description'] = $this->t('Sorry, this store does not support available billing regions.');
        continue;
      }

      $store_country_code = $store->getAddress()->getCountryCode();
      $defaults = [
        'merchant_id' => '',
        'mws_access_key' => '',
        'mws_secret_key' => '',
        'lwa_client_id' => '',
        'region' => $store_country_code == 'GB' ? 'UK' : $store_country_code,
        // @todo port default language code mapping.
        'langcode' => 'en-US',
      ];
      if (!empty($merchant_config[$uuid])) {
        $defaults = $merchant_config[$uuid] + $defaults;
      }

      $form['merchant_information'][$uuid]['merchant_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Merchant ID'),
        '#default_value' => $defaults['merchant_id'],
      ];
      $form['merchant_information'][$uuid]['mws_access_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('MWS Access Key'),
        '#default_value' => $defaults['mws_access_key'],
      ];
      $form['merchant_information'][$uuid]['mws_secret_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('MWS Secret Key'),
        '#default_value' => $defaults['mws_secret_key'],
      ];
      $form['merchant_information'][$uuid]['lwa_client_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('LWA Client ID'),
        '#default_value' => $defaults['lwa_client_id'],
      ];
      $form['merchant_information'][$uuid]['region'] = [
        '#type' => 'radios',
        '#title' => $this->t('Region'),
        '#options' => [
          'DE' => $this->t('Germany'),
          'UK' => $this->t('United Kingdom'),
          'US' => $this->t('United States'),
        ],
        '#default_value' => $defaults['region'],
      ];
      $form['merchant_information'][$uuid]['langcode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Language'),
        '#options' => [
          'en-US' => $this->t('US English'),
          'en-GB' => $this->t('UK English'),
          'de-DE' => $this->t('German'),
          'fr-FR' => $this->t('French'),
          'it-IT' => $this->t('Italian'),
          'es-ES' => $this->t('Spanish (Spain)'),
        ],
        '#default_value' => $defaults['langcode'],
      ];
    }

    $form['advanced'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Advanced settings'),
    ];
    $form['payment']['logging'] = [];

    $simulation_options = [
      '_none' => t('Disabled'),
    ];
    $simulation_options['Authorizations - Declined'] = [
      'Authorizations_InvalidPaymentMethod' => t('Invalid payment method'),
      'Authorizations_AmazonRejected' => t('Amazon rejected'),
      'Authorizations_TransactionTimedOut' => t('Transaction timed out'),
    ];
    $simulation_options['Authorizations - Closed'] = [
      'Authorizations_ExpiredUnused' => t('Expired, unused'),
      'Authorizations_AmazonClosed' => t('Amazon closed'),
    ];
    $simulation_options['Captures'] = [
      'Captures_Pending' => t('Pending'),
      'Captures_AmazonRejected' => t('Declined, Amazon rejected'),
      'Captures_AmazonClosed' => t('Closed, Amazon closed'),
    ];
    $simulation_options['Order Reference - Closed'] = [
      'OrderReference_AmazonClosed' => t('Amazon closed'),
    ];
    $simulation_options['Refund'] = [
      'Refund_AmazonRejected' => t('Declined, Amazon rejected'),
    ];

    $form['payment']['simulation_mode'] = [
      '#title' => t('Sandbox simulation'),
      '#type' => 'select',
      '#description' => $this->t('Simulate different scenarios for testing. See <a href="https://payments.amazon.co.uk/developer/documentation/lpwa/201750790">this documentation</a> for more information'),
      '#options' => $simulation_options,
      '#access' => $config->get('mode') === 'test',
      '#default_value' => $config->get('simulation_mode'),
      '#tree' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    foreach ($values['merchant_information'] as $uuid => $information) {
      if (empty($information['merchant_id'])) {
        unset($values['merchant_information'][$uuid]);
      }
    }
    $this->config('commerce_amazon_lpa.settings')->setData($values)->save();
    parent::submitForm($form, $form_state);
  }

}
