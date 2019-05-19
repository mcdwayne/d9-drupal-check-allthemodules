<?php

namespace Drupal\stripe\Controller;

use Drupal\stripe\StripeService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller that will interact with the service.
 */
class StripeController extends ControllerBase {

  /**
   * Variable that will store the service.
   *
   * @var \Drupal\stripe\StripeService
   */
  protected $stripeService;

  /**
   * {@inheritdoc}
   */
  public function __construct(StripeService $stripeService) {
    $this->stripeService = $stripeService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stripe.stripe_service')
    );
  }

  /**
   * Gets the Publishable Key from Stripe.
   */
  public function getPKey() {
    // Create the customer on Stripe.
    $pKey = $this->stripeService->getPKey();

    return new JsonResponse($pKey);
  }

  /**
   * Gets all the plans on Stripe.
   */
  public function getPlans() {
    // Create the customer on Stripe.
    $plans = $this->stripeService->getPlans();

    return new JsonResponse($plans);
  }

  /**
   * Calls the API to create a new customer using the token to authorize.
   */
  public function createCustomer(Request $request) {
    // Fetch the token info from the POST.
    $token_info = json_decode($request->getContent(), TRUE);

    // Create the customer on Stripe.
    $customer = $this->stripeService->createCustomer($token_info);

    return new JsonResponse($customer);
  }

  /**
   * Gets customer based on customer ID.
   */
  public function getCustomer(Request $request) {
    // Fetch the user info from the POST.
    $customer_info = json_decode($request->getContent(), TRUE);

    // Get the customer from Stripe.
    $customer = $this->stripeService->getCustomer($customer_info);

    return new JsonResponse($customer);
  }

  /**
   * Calls the API to create a new subscription using the user info.
   */
  public function createSubscription(Request $request) {
    // Fetch the user info from the POST.
    $customer = json_decode($request->getContent(), TRUE);

    // Create the customer on Stripe.
    $subscription = $this->stripeService->createSubscription($customer);

    return new JsonResponse($subscription);
  }

  /**
   * Calls the API to cancel an existing subscription using the user info.
   */
  public function cancelSubscription(Request $request) {
    // Fetch the subscription ID info from the POST.
    $subscription_info = json_decode($request->getContent(), TRUE);

    // Cancel the subscription on Stripe.
    $subscription = $this->stripeService->cancelSubscription($subscription_info);

    return new JsonResponse($subscription);
  }

  /**
   * Calls the API to cancel an existing subscription when the time is due.
   */
  public function cancelSubscriptionDue(Request $request) {
    // Fetch the subscription ID info from the POST.
    $subscription_info = json_decode($request->getContent(), TRUE);

    // Cancel the subscription on Stripe when the date is due.
    $subscription = $this->stripeService->cancelSubscriptionDue($subscription_info);

    return new JsonResponse($subscription);
  }

}
