<?php

namespace Drupal\braintree_cashier;

use Drupal\braintree_api\BraintreeApiService;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\BraintreeCustomerCreatedEvent;
use Drupal\braintree_cashier\Event\BraintreeErrorEvent;
use Drupal\braintree_cashier\Event\PaymentMethodUpdatedEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * BillableUser class provides functions that apply to the user entity.
 *
 * @ingroup braintree_cashier
 */
class BillableUser {

  use StringTranslationTrait;

  /**
   * The Braintree Cashier logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The subscription entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * The user entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The braintree cashier service.
   *
   * @var \Drupal\braintree_cashier\BraintreeCashierService
   */
  protected $bcService;

  /**
   * Event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * The Braintree API service.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApiService;

  /**
   * Braintree cashier settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $bcConfig;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * BillableUser constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The Braintree Cashier logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\braintree_cashier\BraintreeCashierService $bcService
   *   The braintree cashier service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   *   The container aware event dispatcher.
   * @param \Drupal\braintree_api\BraintreeApiService $braintreeApiService
   *   The Braintree API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager, BraintreeCashierService $bcService, ContainerAwareEventDispatcher $eventDispatcher, BraintreeApiService $braintreeApiService, ConfigFactoryInterface $configFactory, ThemeManagerInterface $themeManager, MessengerInterface $messenger) {
    $this->logger = $logger;
    $this->subscriptionStorage = $entity_type_manager->getStorage('braintree_cashier_subscription');
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->bcService = $bcService;
    $this->eventDispatcher = $eventDispatcher;
    $this->braintreeApiService = $braintreeApiService;
    $this->bcConfig = $configFactory->get('braintree_cashier.settings');
    $this->themeManager = $themeManager;
    $this->messenger = $messenger;
  }

  /**
   * Updates the payment method for the provided user entity.
   *
   * Deletes the previous payment method so that only one is kept on file.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $nonce
   *   The payment method nonce from the Braintree Drop-in UI.
   *
   * @return bool
   *   A boolean indicating whether the update was successful.
   */
  public function updatePaymentMethod(User $user, $nonce) {

    $customer = $this->asBraintreeCustomer($user);

    $payload = [
      'customerId' => $customer->id,
      'paymentMethodNonce' => $nonce,
    ];
    $result = $this->braintreeApiService->getGateway()->paymentMethod()->create($payload);

    if (!$result->success) {
      $this->logger->error('Error creating payment method: ' . $result->message);
      $event = new BraintreeErrorEvent($user, $result->message, $result);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_ERROR, $event);
      if (!empty($result->creditCardVerification)) {
        $credit_card_verification = $result->creditCardVerification;
        if ($credit_card_verification->status == 'processor_declined') {
          $this->bcService->handleProcessorDeclined($credit_card_verification->processorResponseCode, $credit_card_verification->processorResponseText);
        }
        if ($credit_card_verification->status == 'gateway_rejected') {
          $this->bcService->handleGatewayRejected($credit_card_verification->gatewayRejectionReason);
        }
      }
      else {
        $this->messenger->addError($this->t('Error: @message', ['@message' => $result->message]));
      }
      return FALSE;
    }

    if ($this->bcConfig->get('prevent_duplicate_payment_methods') && !$this->preventDuplicatePaymentMethods($user, $result->paymentMethod)) {
      return FALSE;
    }

    $this->updateSubscriptionsToPaymentMethod($user, $result->paymentMethod->token);
    $this->removeNonDefaultPaymentMethods($user);

    $payment_method_type = get_class($result->paymentMethod);

    $event = new PaymentMethodUpdatedEvent($user, $payment_method_type);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::PAYMENT_METHOD_UPDATED, $event);

    return TRUE;
  }

  /**
   * Gets the Braintree customer.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return \Braintree\Customer
   *   The Braintree customer object.
   *
   * @throws \Braintree\Exception\NotFound
   *   The Braintree not found exception.
   */
  public function asBraintreeCustomer(User $user) {
    return $this->braintreeApiService->getGateway()->customer()->find($this->getBraintreeCustomerId($user));
  }

  /**
   * Gets the Braintree customer ID.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return string
   *   The Braintree customer ID.
   */
  public function getBraintreeCustomerId(UserInterface $user) {
    return $user->get('braintree_customer_id')->value;
  }

  /**
   * Updates all subscriptions to use the payment method with the given token.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $token
   *   The payment method token.
   */
  public function updateSubscriptionsToPaymentMethod(User $user, $token) {
    foreach ($this->getSubscriptions($user) as $subscription_entity) {
      /* @var $subscription_entity \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface */
      $this->braintreeApiService->getGateway()->subscription()->update(
        $subscription_entity->getBraintreeSubscriptionId(), [
          'paymentMethodToken' => $token,
        ]);
    }
  }

  /**
   * Gets the subscription entities for a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   * @param bool $active
   *   Whether to return only subscriptions that are currently active.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of subscription entities.
   */
  public function getSubscriptions(UserInterface $user, $active = TRUE) {
    $query = $this->subscriptionStorage->getQuery();
    $query->condition('subscribed_user.target_id', $user->id());
    if ($active) {
      $query->condition('status', BraintreeCashierSubscriptionInterface::ACTIVE);
    }
    $result = $query->execute();
    if (!empty($result)) {
      return $this->subscriptionStorage->loadMultiple($result);
    }
    return [];
  }

  /**
   * Remove non-default payment methods.
   *
   * This keeps the Drop-in UI simple since otherwise all payment methods are
   * always shown.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @throws \Braintree\Exception\NotFound
   *   The Braintree not found exception.
   */
  public function removeNonDefaultPaymentMethods(User $user) {
    $customer = $this->asBraintreeCustomer($user);

    foreach ($customer->paymentMethods as $paymentMethod) {
      if (!$paymentMethod->isDefault()) {
        $this->braintreeApiService->getGateway()->paymentMethod()->delete($paymentMethod->token);
      }
    }
  }

  /**
   * Gets a Braintree payment method.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return \Braintree_PaymentMethod|bool
   *   The Braintree payment method object, or FALSE if none found.
   *
   * @throws \Braintree\Exception\NotFound
   *   The Braintree not found exception.
   */
  public function getPaymentMethod(User $user) {
    $customer = $this->asBraintreeCustomer($user);
    foreach ($customer->paymentMethods as $paymentMethod) {
      if ($paymentMethod->isDefault()) {
        return $paymentMethod;
      }
    }
    return FALSE;
  }

  /**
   * Creates a new Braintree customer.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $nonce
   *   The payment method nonce from the Drop-in UI.
   *
   * @return \Braintree_Customer|bool
   *   The Braintree customer object.
   */
  public function createAsBraintreeCustomer(User $user, $nonce) {
    $result = $this->braintreeApiService->getGateway()->customer()->create([
      'firstName' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'paymentMethodNonce' => $nonce,
    ]);

    if (!$result->success) {
      $this->logger->error('Error creating Braintree customer: ' . $result->message);
      $event = new BraintreeErrorEvent($user, $result->message, $result);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_ERROR, $event);
      if (!empty($result->creditCardVerification)) {
        $credit_card_verification = $result->creditCardVerification;
        if ($credit_card_verification->status == 'processor_declined') {
          $this->bcService->handleProcessorDeclined($credit_card_verification->processorResponseCode, $credit_card_verification->processorResponseText);
        }
        if ($credit_card_verification->status == 'gateway_rejected') {
          $this->bcService->handleGatewayRejected($credit_card_verification->gatewayRejectionReason);
        }
        return FALSE;
      }
      foreach ($result->errors->deepAll() as $error) {
        // @see https://developers.braintreepayments.com/reference/general/validation-errors/all/php#code-81724
        if ($error->code = '81724') {
          $this->messenger->addError($this->bcConfig->get('duplicate_payment_method_message'));
          return FALSE;
        }
      }
      $this->messenger->addError($this->t('Card declined: @message', ['@message' => $result->message]));
      return FALSE;
    }

    // Check for duplicate payment methods.
    if ($this->bcConfig->get('prevent_duplicate_payment_methods')) {
      foreach ($result->customer->paymentMethods as $payment_method) {
        if (!$this->preventDuplicatePaymentMethods($user, $payment_method)) {
          $this->braintreeApiService->getGateway()->customer()->delete($result->customer->id);
          return FALSE;
        }
      }
    }

    $this->logger->notice('A new Braintree Customer has been created with Braintree Customer ID: %id', [
      '%id' => $result->customer->id,
    ]);

    $user->set('braintree_customer_id', $result->customer->id);
    $user->save();

    $event = new BraintreeCustomerCreatedEvent($user);
    $this->eventDispatcher->dispatch(BraintreeCashierEvents::BRAINTREE_CUSTOMER_CREATED, $event);

    // Invalidate the local tasks cache to make the "Invoices" task appear when
    // viewed by other users such as administrators.
    $theme_machine_name = $this->themeManager->getActiveTheme()->getName();
    Cache::invalidateTags(['config:block.block.' . $theme_machine_name . '_local_tasks']);

    return $result->customer;
  }

  /**
   * Sets the user-provided invoice billing information.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity with the provided billing information.
   * @param string $billing_information
   *   The billing information.
   *
   * @return \Drupal\user\Entity\User
   *   The user entity.
   */
  public function setInvoiceBillingInformation(User $user, $billing_information) {
    $user->set('invoice_billing_information', $billing_information);
    $user->save();
    return $user;
  }

  /**
   * Gets the user-provided invoice billing information for the user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return string
   *   The billing information markup.
   */
  public function getInvoiceBillingInformation(User $user) {
    return check_markup($user->get('invoice_billing_information')->value, $user->get('invoice_billing_information')->format);
  }

  /**
   * Gets the user's billing information as plain text.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   *
   * @return mixed
   *   The plain text billing information.
   */
  public function getRawInvoiceBillingInformation(User $user) {
    return $user->get('invoice_billing_information')->value;
  }

  /**
   * Generate client token for the Drop-in UI for the provided user entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity which might have a Braintree customer ID.
   * @param int $version
   *   The Braintree API version.
   *
   * @see https://developers.braintreepayments.com/reference/request/client-token/generate/php#version
   *   For documentation about the version.
   *
   * @return string
   *   The Braintree Client Token.
   */
  public function generateClientToken(User $user = NULL, $version = 3) {
    $version = $this->sanitizeVersion($version);
    try {
      $payload = [
        'version' => $version,
      ];
      if ($user !== NULL && !empty($this->getBraintreeCustomerId($user))) {
        $payload['customerId'] = $this->getBraintreeCustomerId($user);
        $payload['options'] = [
          'makeDefault' => TRUE,
        ];
      }
      return $this->braintreeApiService->getGateway()->clientToken()->generate($payload);
    }
    catch (\InvalidArgumentException $e) {
      // The customer id provided probably doesn't exist with Braintree.
      $this->logger->error('InvalidArgumentException occurred in generateClientToken: ' . $e->getMessage());
      $this->messenger->addError($this->t('Our payment processor reported the following error: %error. Please contact the site administrator.', [
        '%error' => $e->getMessage(),
      ]));
    }
    catch (\Exception $e) {
      // There was probably an API error of some kind. Either API credentials
      // are not configured properly, or there's an issue with Braintree.
      $this->logger->error('Exception in generateClientToken(): ' . $e->getMessage());
      $this->messenger->addError($this->t('Our payment processor reported the following error: %error. Please try reloading the page.', ['%error' => $e->getMessage()]));
    }
  }

  /**
   * Sanitizes the version number.
   *
   * @param int $version
   *   The Braintree API version.
   *
   * @return int
   *   A version which is guaranteed to be valid.
   */
  private function sanitizeVersion($version) {
    if (!in_array($version, [1, 2, 3])) {
      $version = 3;
    }
    return $version;
  }

  /**
   * Gets the form API array for the Braintree Drop-in UI element.
   *
   * This function permits updating the version of the drop-in UI in only one
   * place since the drop-in UI is used in multiple forms.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user account for which to generate the Drop-in UI.
   *
   * @return array
   *   The Drop-in UI form element.
   */
  public function getDropinUiFormElement(EntityInterface $user = NULL) {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'src' => 'https://js.braintreegateway.com/web/dropin/1.11.0/js/dropin.min.js',
        'data-braintree-dropin-authorization' => $this->generateClientToken($user),
      ],
    ];
    if ($this->bcConfig->get('accept_paypal')) {
      $element['#attributes']['data-paypal.flow'] = 'vault';
    }
    return $element;
  }

  /**
   * Check whether a payment method is in use by a different account.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user account that wishes to own the payment method.
   * @param mixed $payment_method
   *   The payment method object.
   *
   * @return array|bool
   *   An array of other uids that have this payment method, or FALSE if
   *   no other account is using this payment method.
   */
  public function isDuplicatePaymentMethod(User $user, $payment_method) {
    $query = $this->userStorage->getQuery();
    if ($payment_method instanceof \Braintree_CreditCard) {
      $identifier = $payment_method->uniqueNumberIdentifier;
    }
    if ($payment_method instanceof \Braintree_PayPalAccount) {
      $identifier = $payment_method->email;
    }
    $query->condition('payment_method_identifier', $identifier);
    $uids = $query->execute();
    if (empty($uids) || (\count($uids) === 1 && \in_array($user->id(), $uids, TRUE))) {
      // Either no user owns this payment method, or only the given user does.
      return FALSE;
    }
    return $uids;
  }

  /**
   * Records the payment method identifier on the user entity.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user account that wishes to own the payment method.
   * @param mixed $payment_method
   *   The payment method object.
   *
   * @return bool
   *   A boolean indicating whether the identifier was successfully recorded.
   */
  public function recordPaymentMethodIdentifier(User $user, $payment_method) {
    if ($payment_method instanceof \Braintree_CreditCard) {
      $identifier = $payment_method->uniqueNumberIdentifier;
    }
    if ($payment_method instanceof \Braintree_PayPalAccount) {
      $identifier = $payment_method->email;
    }
    if (empty($identifier)) {
      return FALSE;
    }
    $user->set('payment_method_identifier', $identifier);
    $user->save();
    return TRUE;
  }

  /**
   * Prevent duplicate payment methods.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user account that wishes to own the payment method.
   * @param mixed $payment_method
   *   The payment method object.
   *
   * @return bool
   *   A boolean indicating success with preventing duplicate payment methods.
   */
  public function preventDuplicatePaymentMethods(User $user, $payment_method) {
    if (!empty($uids = $this->isDuplicatePaymentMethod($user, $payment_method))) {
      $this->braintreeApiService->getGateway()->paymentMethod()->delete($payment_method->token);
      $message = Message::create([
        'template' => 'duplicate_payment_method',
        'uid' => $user->id(),
        'field_duplicate_user' => $uids,
      ]);
      $message->save();
      $this->messenger->addError($this->bcConfig->get('duplicate_payment_method_message'));
      $this->logger->error('Duplicate payment method. User account uids with this payment method: %uids',
        ['%uids' => print_r($uids, TRUE)]);
      return FALSE;
    }
    if (!$this->recordPaymentMethodIdentifier($user, $payment_method)) {
      $this->braintreeApiService->getGateway()->paymentMethod()->delete($payment_method->token);
      $message = $this->t('There was a problem with your payment method. Please try again, or contact a site administrator');
      $this->messenger->addError($message);
      $this->logger->error($message);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Update the vaulted email address stored in Braintree for a given user.
   *
   * The email address in Braintree will be set to the current email of the
   * provided user entity. Before utilizing this function, ensure that the user
   * entity is already vaulted in Braintree by checking for a Braintree customer
   * ID.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity for which to update the email address.
   */
  public function updateVaultedEmail(UserInterface $user) {
    $gateway = $this->braintreeApiService->getGateway();
    $gateway->customer()->update($this->getBraintreeCustomerId($user), [
      'email' => $user->getEmail(),
    ]);
    $this->logger->notice('Updated email address in Braintree vault to @new_email', [
      '@new_email' => $user->getEmail(),
    ]);
  }

}
