<?php

namespace Drupal\commerce_worldline;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Sips\Passphrase;
use Sips\PaymentResponse;
use Sips\ShaComposer\AllParametersShaComposer;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ValidateExternalPayment.
 */
class ValidateExternalPayment {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentStorage;

  /**
   * The payment gateway configuration.
   *
   * @var array
   */
  protected $config;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ValidateExternalPayment constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param string[] $config
   *   The gateway config.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, array $config) {
    $this->paymentStorage = $entityTypeManager->getStorage('commerce_payment');
    $this->config = $config;
    $this->logger = \Drupal::logger('commerce_worldline');
  }

  /**
   * Validate external request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to validate for.
   *
   * @return bool
   *   Returns the validity of the request.
   */
  public function validateRequest(Request $request, OrderInterface $order) {
    // Prepare the PaymentRequest from the global request data.
    $payment_response = new PaymentResponse($request->request->all());

    $transaction_reference = $payment_response->getParam('transactionReference');

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->paymentStorage->load($transaction_reference);
    if ($payment === FALSE || $payment === NULL) {
      // This is not a valid response because no payment was found. We're
      // aborting the request here.
      $context = [
        'transactionReference' => $transaction_reference,
      ];
      $this->logger
        ->warning('User arrived to commerce_worldline.handle_response without valid information', $context);

      drupal_set_message($this->t('An error occurred while processing your request.'), 'error');
      throw new PaymentGatewayException('An error occurred while processing your request.');
    }

    // Prepares the validation.
    $passphrase = new Passphrase($this->config['sips_passphrase']);
    $shaComposer = new AllParametersShaComposer($passphrase);

    if (!$payment_response->isValid($shaComposer)) {
      // This is not a valid response because the transaction reference from the
      // request is not matching with the payment reference. We're aborting the
      // request here.
      $context = [
        'transactionReference' => $transaction_reference,
        'transactionReferencePayment' => $payment->getRemoteId(),
        'remoteState' => $payment->getRemoteState(),
        'valid' => $payment_response->isValid($shaComposer) ? 'Yes' : 'No',
      ];
      $this->logger
        ->warning('User arrived to commerce_worldline.handle_response without valid information', $context);

      drupal_set_message($this->t('An error occurred while processing your request.'), 'error');
      throw new PaymentGatewayException('An error occurred while processing your request.');
    }

    // Update the payment method with the response code.
    $code = $payment_response->getParam('RESPONSECODE');
    $payment->set('sips_response_code', $code);

    // Check if the payment is pending to be processed and update it with the
    // successful information.
    if ($payment_response->isSuccessful()) {
      $payment->setRemoteState('done');
      $payment->setState('completed');
      $payment->save();
      return TRUE;
    }

    // Payment wasn't successful:
    // Update the payment information.
    $payment->setRemoteState('failed');
    $payment->setState('void');
    $payment->save();

    drupal_set_message($this->t('An error occurred in the SIPS platform: [@code] @error',
      [
        '@error' => $this->getResponseCodeDescription($code),
        '@code' => $code,
      ]), 'error');

    throw new PaymentGatewayException("An error occurred in the SIPS platform: [{$this->getResponseCodeDescription($code)}] {$code}");
  }

  /**
   * Get the SIPS response description.
   *
   * @param string $code
   *   Response code.
   *
   * @return string
   *   Description for the response code.
   */
  protected function getResponseCodeDescription($code) {
    $descriptions = [
      '00' => 'Authorisation accepted',
      '02' => 'Authorisation request to be performed via telephone with the issuer, as the card authorisation threshold has been exceeded, if the forcing is authorised for the merchant',
      '03' => 'Invalid distance selling contract',
      '05' => 'Authorisation refused',
      '12' => 'Invalid transaction, verify the parameters transferred in the request.',
      '14' => 'invalid bank details or card security code',
      '17' => 'Buyer cancellation',
      '24' => 'Operation impossible. The operation the merchant wishes to perform is not compatible with the status of the transaction.',
      '25' => 'Transaction not found in the Sips database',
      '30' => 'Format error',
      '34' => 'Suspicion of fraud',
      '40' => 'Function not supported: the operation that the merchant would like to perform is not part of the list of operations for which the merchant is authorised',
      '51' => 'mount too high',
      '54' => 'Card is past expiry date',
      '60' => 'Transaction pending',
      '63' => 'Security rules not observed, transaction stopped',
      '75' => 'Number of attempts at entering the card number exceeded',
      '90' => 'Service temporarily unavailable',
      '94' => 'Duplicated transaction: for a given day, the TransactionReference has already been used',
      '97' => 'Timeframe exceeded, transaction refused',
      '99' => 'Temporary problem at the Sips Office Server level',
    ];

    if (empty($descriptions[$code])) {
      return "Unknown error code - [{$code}]";
    }

    return $descriptions[$code];
  }

}
