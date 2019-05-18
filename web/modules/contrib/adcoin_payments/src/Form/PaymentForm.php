<?php
/**
 * A payment form that can be displayed on the website.
 * @author appels
 */
namespace Drupal\adcoin_payments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\adcoin_payments\Model\PaymentStorage;
use Drupal\adcoin_payments\Model\Settings;
use Drupal\adcoin_payments\Exception\ExceptionHandler;
use Drupal\adcoin_payments\Exception\SubmissionException;
use Drupal\adcoin_payments\WalletAPIWrapper\PaymentGateway;

use Symfony\Component\HttpFoundation\RedirectResponse;

class PaymentForm extends FormBase {
  // Array of values defined by the user when inserting the block
  private $config;



  /**
   * Set configuration values.
   *
   * @param array $config Block configuration values.
   */
  public function setConfiguration(array $config) {
    $this->config = $config;
  }

  /**
   * Check whether a field for this form is enabled.
   *
   * @param $field Field's system (POST) name.
   *
   * @return bool
   */
  private function isFieldEnabled($field) {
    return $this->config['enable_'.$field];
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adcoin_payments_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Name text field
    if ($this->isFieldEnabled('name')) {
      $form['name'] = [
        '#type'  => 'textfield',
        '#name'  => 'name',
        '#placeholder' => t('Your name'),
        '#required' => TRUE
      ];
    }

    // Email text field
    if ($this->isFieldEnabled('email')) {
      $form['email'] = [
        '#type'  => 'email',
        '#name'  => 'email',
        '#placeholder' => t('Your email'),
        '#required' => TRUE
      ];
    }

    // Phone number field
    if ($this->isFieldEnabled('phone')) {
      $form['phone'] = [
        '#type'  => 'textfield',
        '#name'  => 'phone',
        '#placeholder' => t('Your phone number'),
        '#required' => TRUE
      ];
    }

    // Postal/zipcode field
    if ($this->isFieldEnabled('postal')) {
      $form['postal'] = [
        '#type'  => 'textfield',
        '#name'  => 'postal',
        '#placeholder' => t('Your postal/zipcode'),
        '#required' => TRUE
      ];
    }

    // Country field
    if ($this->isFieldEnabled('country')) {
      $form['country'] = [
        '#type'  => 'textfield',
        '#name'  => 'country',
        '#placeholder' => t('Your country'),
        '#required' => TRUE
      ];
    }

    // Custom submit button
    $button_text = isset($this->config['button_text']) ? $this->config['button_text'] : '';
    $form['actions']['submit'] = [
      '#type'   => 'submit',
      '#value'  => $button_text,
      '#prefix' => '<div class="adcoin-payments-pay-button">',
      '#suffix' => '</div>',
    ];
    $form['actions']['submit']['#attributes']['class'][] = 'adcoin-payments-pay-button';

    // Attach form CSS
    $form['#attached']['library'][] = 'adcoin_payments/adcoin_payments_payment_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Opens a payment on the AdCoin Payment Gateway.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      // Fetch Wallet API key
      if (!($api_key = Settings::fetchApiKey()))
        throw new SubmissionException('API key has not been configured.');

      // Make return and webhook URLs
      $url_host    = \Drupal::request()->getSchemeAndHttpHost();
      $url_return  = $url_host . \Drupal::url('adcoin_payments.return');
      $url_webhook = $url_host . \Drupal::url('adcoin_payments.webhook');

      // Fetch form values
      $values  = $form_state->getValues();
      $name    = $this->isFieldEnabled('name')    ? $values['name']    : '';
      $email   = $this->isFieldEnabled('email')   ? $values['email']   : '';
      $phone   = $this->isFieldEnabled('phone')   ? $values['phone']   : '';
      $postal  = $this->isFieldEnabled('postal')  ? $values['postal']  : '';
      $country = $this->isFieldEnabled('country') ? $values['country'] : '';

      try {
        /*
         * Open a new payment on the AdCoin Payment Gateway.
         * The metadata that will be sent to the gateway consists of the filled
         * in form fields and the success and cancel URLs.
         */
        $gateway = new PaymentGateway($api_key);
        $gateway_payment = $gateway->openPayment(
          (float)$this->config['amount'],
          $this->config['description'],
          $url_return,
          $url_webhook,
          [ // metadata
            'name'          => $name,
            'email'         => $email,
            'route_success' => $this->config['route_success'],
            'route_cancel'  => $this->config['route_cancel']
          ]
        );
      } catch (\Exception $e) {
        throw new SubmissionException($e->getMessage());
      }

      // Convert payment creation timestamp to MySQL format
      $created_at = date("Y-m-d H:i:s", strtotime($gateway_payment['created_at']));

      // Create a new payment record
      PaymentStorage::paymentOpen(
        $gateway_payment['id'],
        $created_at,
        $name,
        $email,
        $phone,
        $postal,
        $country,
        (int)$this->config['amount']
      );

      // Redirect user to payment gateway
      $response = new RedirectResponse($gateway_payment['links']['paymentUrl']);
      $response->send();

    } catch (\Exception $e) {
      ExceptionHandler::handle($e);
    }

  }
}