<?php

namespace Drupal\commerce_paymetric\PluginForm;

use CommerceGuys\AuthNet\DataTypes\TransactionRequest;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_paymetric\lib\PaymetricTransaction;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a checkout form for our offsite payment.
 */
class PaymetricOffsiteCheckoutForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $form['#attached']['library'][] = 'commerce_payment/payment_method_form';
    $form['#tree'] = TRUE;
    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => $payment_method->bundle(),
    ];
    $form['payment_details'] = $this->buildCreditCardForm($form['payment_details'], $form_state);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Complete purchase'),
    ];

    return $form;
  }

  /**
   * Builds the credit card form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built credit card form.
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Build a month select list that shows months with a leading zero.
    $months = [];
    for ($i = 1; $i < 13; $i++) {
      $month = str_pad($i, 2, '0', STR_PAD_LEFT);
      $months[$month] = $month;
    }
    // Build a year select list that uses a 4 digit key with a 2 digit value.
    $current_year_4 = date('Y');
    $current_year_2 = date('y');
    $years = [];
    for ($i = 0; $i < 10; $i++) {
      $years[$current_year_4 + $i] = $current_year_2 + $i;
    }

    $element['#attributes']['class'][] = 'credit-card-form';
    // Placeholder for the detected card type. Set by validateCreditCardForm().
    $element['type'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => t('Card number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 19,
      '#size' => 20,
    ];
    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['credit-card-form__expiration'],
      ],
    ];
    $element['expiration']['month'] = [
      '#type' => 'select',
      '#title' => t('Month'),
      '#options' => $months,
      '#default_value' => date('m'),
      '#required' => TRUE,
    ];
    $element['expiration']['divider'] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<span class="credit-card-form__divider">/</span>',
    ];
    $element['expiration']['year'] = [
      '#type' => 'select',
      '#title' => t('Year'),
      '#options' => $years,
      '#default_value' => $current_year_4,
      '#required' => TRUE,
    ];
    $element['security_code'] = [
      '#type' => 'textfield',
      '#title' => t('CVV'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->validateCreditCardForm($form['payment_details'], $form_state);
    if (!empty($form_state->getErrors())) {
      return FALSE;
    }

    /** @var \Drupal\commerce_log\LogStorageInterface $commerce_log */
    $commerce_log = \Drupal::entityTypeManager()->getStorage('commerce_log');
    try {
      $payment_details = $form_state->getValue('payment_process')['offsite_payment']['payment_details'];
      /** @var \Drupal\commerce_paymetric\Plugin\Commerce\PaymentGateway\PaymetricOffsitePaymentGateway $plugin */
      $plugin = $this->plugin;
      $authorization = $plugin->authorizePaymentMethod($this, $payment_details);
      $transaction_id = $authorization->TransactionID;
      $batch_id = $authorization->BatchID;
      $form_state->set('transaction_id', $transaction_id);
      $form_state->set('batch_id', $batch_id);
      $response_code = $authorization->ResponseCode;
      $status_code = $authorization->StatusCode;
      if ($authorization instanceof PaymetricTransaction) {
        if ($response_code < 0 || $status_code < 0) {
          $form_state->setError($form['payment_details']['number'], 'There was an error processing your credit card.');

          // Saves to the order.
          $commerce_log->generate($this->getEntity()->getOrder(), 'paymetric_payment_error', [
            'data' => $authorization->Message,
          ])->save();
          return FALSE;
        }
        else {
          $commerce_log->generate($this->getEntity()->getOrder(), 'paymetric_default', [
            'data' => t('The transaction has been successfully authorized.'),
          ])->save();
        }
      }
      else {
        // Saves to the order.
        $commerce_log->generate($this->getEntity()->getOrder(), 'paymetric_payment_error', [
          'data' => $authorization->Message,
        ])->save();
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $form_state->setError($form['payment_details']['number'], 'There was an error processing your credit card. Please try again later.');
      $commerce_log->generate($this->getEntity()->getOrder(), 'paymetric_payment_error', [
        'data' => $e->getMessage(),
      ])->save();
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validates the credit card form.
   *
   * @param array $element
   *   The credit card form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);
    $card_type = CreditCard::detectType($values['number']);
    if (!$card_type) {
      $form_state->setError($element['number'], t('You have entered a credit card number of an unsupported card type.'));
      return;
    }
    if (!CreditCard::validateNumber($values['number'], $card_type)) {
      $form_state->setError($element['number'], t('You have entered an invalid credit card number.'));
    }
    if (!CreditCard::validateExpirationDate($values['expiration']['month'], $values['expiration']['year'])) {
      $form_state->setError($element['expiration'], t('You have entered an expired credit card.'));
    }
    if (!CreditCard::validateSecurityCode($values['security_code'], $card_type)) {
      $form_state->setError($element['security_code'], t('You have entered an invalid CVV.'));
    }

    // Persist the detected card type.
    $form_state->setValueForElement($element['type'], $card_type->getId());
  }

  /**
   * Call the plugins implementation of the create payment here.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_paymetric\Plugin\Commerce\PaymentGateway\Paymetric $plugin */
    $plugin = $this->plugin;
    if ($plugin->getTransactionType() == TransactionRequest::AUTH_CAPTURE) {

      try {
        // Card details.
        $payment_details = $form_state->getValue('payment_process')['offsite_payment']['payment_details'];
        $payment_details['transaction_id'] = $form_state->get('transaction_id');

        $settlement = $plugin->createPaymentMethod($this, $payment_details);
        /** @var \Drupal\commerce_log\LogStorageInterface $commerce_log */
        $commerce_log = \Drupal::entityTypeManager()->getStorage('commerce_log');
        if ($settlement->StatusCode == 200) {
          $commerce_log->generate($this->getEntity()->getOrder(), 'paymetric_payment_error', [
            'data' => 'The payment was captured successfully.',
          ])->save();
        }
        $data = [
          'amount' => $settlement->SettlementAmount,
          'currency' => $settlement->CurrencyKey,
          'transactionId' => $settlement->TransactionID,
          'message' => $settlement->Message,
        ];
        $url = Url::fromRoute('commerce_payment.checkout.return', ['commerce_order' => $this->getEntity()->getOrder()->id(), 'step' => 'payment']);
        $this->buildRedirectForm($form, $form_state, \Drupal::request()->getSchemeAndHttpHost() . $url->toString(), $data, 'get');
      }
      catch (InvalidPluginDefinitionException $e) {
      }
      catch (PluginNotFoundException $e) {
      }
    }
  }

}
