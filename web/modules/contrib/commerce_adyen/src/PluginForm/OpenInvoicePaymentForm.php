<?php

namespace Drupal\commerce_adyen\PluginForm;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_adyen\Adyen\Authorisation\Request;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use germanoricardi\helpers\BrazilianHelper;

/**
 * OpenInvoice Form.
 */
class OpenInvoicePaymentForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['gender'] = [
      '#type' => 'radios',
      '#title' => t('Gender'),
      '#options' => [
        'MALE' => t('Male'),
        'FEMALE' => t('Female'),
      ],
      '#required' => TRUE,
    ];

    $form['phone_number'] = [
      '#type' => 'textfield',
      '#title' => t('Phone number'),
      '#required' => TRUE,
    ];

    $date = '1970-01-01';
    $form['birth_date'] = [
      '#type' => 'date',
      '#title' => t('Date of Birth'),
      '#default_value' => $date,
    ];

    $form['social_number'] = [
      '#type' => 'textfield',
      '#title' => t('Social security number'),
      '#description' => t("The social security number."),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t("Continue"),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $fom_values = $form_state->getValues();
    $social_number = $fom_values['payment_process']['offsite_payment']['social_number'];
    if (!empty($social_number)) {
      $helper = new BrazilianHelper();

      switch (strlen(preg_replace('/[^0-9]/', '', $social_number))) {
        // @see https://en.wikipedia.org/wiki/Cadastro_de_Pessoas_F%C3%ADsicas
        case 11:
          $result = $helper->asCpf($social_number);
          $type = 'CPF';
          break;

        // @see https://en.wikipedia.org/wiki/CNPJ
        case 14:
          $result = $helper->asCnpj($social_number);
          $type = 'CNPJ';
          break;

        default:
          $form_state->setErrorByName('social_number', t('Please fill valid CPF/CNPJ (11 or 14 characters long).'));
          return FALSE;
      }

      if (NULL === $result) {
        $form_state->setErrorByName('social_number', t('@type number you have entered is invalid.', ['@type' => $type]));
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $payment = $this->entity;
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();
    /** @var \Drupal\profile\Entity\Profile $billing_profile */
    $billing_profile = $order->getBillingProfile();
    $billing_profile_values = $billing_profile->toArray();
    $address = $billing_profile_values['address'][0];
    $fom_values = $form_state->getValues();
    $gender = $fom_values['payment_process']['offsite_payment']['gender'];
    $phone_number = $fom_values['payment_process']['offsite_payment']['phone_number'];
    $birth_date = $fom_values['payment_process']['offsite_payment']['birth_date'];
    $social_number = $fom_values['payment_process']['offsite_payment']['social_number'];

    $adyen_order = new \stdClass();
    $adyen_order->order_id = $order->id();
    $adyen_order->order_number = $order->id();
    $adyen_order->uid = $billing_profile_values['uid']['target_id'];
    $adyen_order->owner = [
      'name' => $address['given_name'] . " " . $address['family_name'],
    ];
    $adyen_order->mail = $order->getEmail();
    $adyen_order->status = 1;
    $adyen_order->ship_before_date = "";
    $adyen_order->data = [
      'commerce_adyen_payment_type' => 'openinvoice',
      'openinvoice' => [
        'gender' => $gender,
        'phone_number' => $phone_number,
        'birth_date' => $birth_date,
        'social_number' => $social_number,
      ],
      'payment_redirect_key' => "",
    ];
    $adyen_order->commerce_order_total = [
      'amount' => $payment->getAmount()->getNumber(),
      'currency_code' => $payment->getAmount()->getCurrencyCode(),
    ];
    $adyen_order->commerce_customer_billing = [
      'commerce_customer_address' => [
        'country' => $address['country_code'],
      ],
    ];
    $gateway = $payment->getPaymentGateway();
    $gateway_configuration = $gateway->get('configuration');

    $adyen_payment_method = [
      'settings' => [
        'mode' => $gateway_configuration['mode'],
        'merchant_account' => $gateway_configuration['merchant_account'],
        'client_user' => $gateway_configuration['client_user'],
        'client_password' => $gateway_configuration['client_password'],
        'skin_code' => $gateway_configuration['skin_code'],
        'hmac' => $gateway_configuration['hmac'],
        'shopper_locale' => $gateway_configuration['shopper_locale'],
        'recurring' => $gateway_configuration['recurring'],
        'state' => $gateway_configuration['state'],
        'payment_types' => [
          'parameter__payment_method__settings__payment_method__settings__payment_types__active_tab' => 'openinvoice',
        ],
        'default_payment_type' => 'openinvoice',
        'use_checkout_form' => $gateway_configuration['use_checkout_form'],
      ],
    ];
    $adyen_payment = new Request($adyen_order, $adyen_payment_method);
    $adyen_payment->setSessionValidity(strtotime('+ 2 hour'));
    $adyen_payment->setShopperLocale(\Drupal::languageManager()->getCurrentLanguage()->getId());
    $adyen_payment->signRequest();

    // Redirect.
    $data = [
      'countryCode' => $adyen_payment->getCountryCode(),
      'currencyCode' => $adyen_payment->getCurrencyCode(),
      'merchantAccount' => $adyen_payment->getMerchantAccount(),
      'merchantReference' => $adyen_payment->getMerchantReference(),
      'merchantReturnData' => $adyen_payment->getMerchantReturnData(),
      'merchantSig' => $adyen_payment->getMerchantSig(),
      'paymentAmount' => $adyen_payment->getPaymentAmount(),
      'resURL' => $adyen_payment->getResUrl(),
      'sessionValidity' => $adyen_payment->getSessionValidity(),
      'shipBeforeDate' => $adyen_payment->getShipBeforeDate(),
      'shopperEmail' => $adyen_payment->getShopperEmail(),
      'shopperIP' => $adyen_payment->getShopperIp(),
      'shopperInteraction' => $adyen_payment->getShopperInteraction(),
      'shopperLocale' => $adyen_payment->getShopperLocale(),
      'shopperReference' => $adyen_payment->getShopperReference(),
      'skinCode' => $adyen_payment->getSkinCode(),
    ];
    $redirect_url = Url::fromUri($adyen_payment->getEndpoint(),
      [
        'absolute' => TRUE,
        'query' => $data,
      ]
    )->toString();
    throw new NeedsRedirectException($redirect_url);
  }

}
