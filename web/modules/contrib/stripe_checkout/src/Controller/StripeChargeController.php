<?php

namespace Drupal\stripe_checkout\Controller;

use Stripe\Charge;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\stripe_api\StripeApiService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Session\AccountProxy;

/**
 * Class StripeChargeController.
 *
 * @package Drupal\stripe_checkout\Controller
 */
class StripeChargeController extends ControllerBase {

  /**
   * Drupal\stripe_api\StripeApiService definition.
   *
   * @var \Drupal\stripe_api\StripeApiService
   */
  protected $stripeApiStripeApi;
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(StripeApiService $stripe_api_stripe_api, AccountProxy $current_user) {
    $this->stripeApiStripeApi = $stripe_api_stripe_api;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stripe_api.stripe_api'),
      $container->get('current_user')
    );
  }

  /**
   * Charge.
   *
   * @return string
   *   Return Hello string.
   */
  public function charge(Request $request) {

    try {
      $token = $request->get('stripeToken');
      $entity_id = $request->get('entity_id');
      $field_name = $request->get('field_name');

      if (!$token || !$entity_id || !$field_name) {
        throw new \Exception("Required data is missing!");
      }

      // Load the price from the node that was purchased.
      $node = $this->entityTypeManager()->getStorage('node')->load($entity_id);
      $amount = $node->$field_name->value;
      $field_settings = $node->$field_name->getSettings();
      $currency = $field_settings['currency'];
      $user = $this->currentUser->getAccount();

      $charge = Charge::create([
        // Convert to cents.
        "amount" => $amount * 100,
        "source" => $token,
        "description" => $this->t('Purchase of @title', ['@title' => $node->label()]),
        'currency' => $currency,
        'receipt_email' => $request->request->get('stripeEmail'),
        "metadata" => [
          'module' => 'stripe_checkout',
          'entity_id' => $entity_id,
          'uid' => $user->id(),
        ],
      ]);

      if ($charge->paid === TRUE) {
        drupal_set_message($this->t("Thank you. Your payment has been processed."));

        // At this point a Stripe webhook should make a request to
        // the stripe_api.webhook route, which will dispatch an event
        // to which event subscribers can react.
        $this->getLogger('stripe_checkout.logger')->info(t("Successfully processed Stripe charge. This should have triggered a Stripe webhook. \nsubmitted data:@data", [
          '@data' => $request->getContent(),
        ]));

        // In addition to the webhook, we fire a traditional Drupal hook
        // to permit other modules to respond to this event instantaneously.
        $this->moduleHandler()->invokeAll('stripe_checkout_charge_succeeded', [
          $charge,
          $node,
          $user,
        ]);

        return $this->redirect('entity.node.canonical', ['node' => $entity_id]);
      }
      else {
        drupal_set_message($this->t("Payment failed."), 'error');

        $this->getLogger('stripe_checkout.logger')->error($this->t("Could not complete Stripe charge. \nsubmitted data:@data", [
          '@data' => $request->getContent(),
        ]));

        return new Response(NULL, Response::HTTP_FORBIDDEN);
      }

    }
    catch (\Exception $e) {
      drupal_set_message($this->t("Payment failed."), 'error');

      $this->getLogger('stripe_checkout.logger')->error($this->t("Could not complete Stripe charge, error:\n@error\nsubmitted data:@data", [
        '@data' => $request->getContent(),
        '@error' => $e->getMessage(),
      ]));
      return new Response(NULL, Response::HTTP_FORBIDDEN);
    }
  }

}
