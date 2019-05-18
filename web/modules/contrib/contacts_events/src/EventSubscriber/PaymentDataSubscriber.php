<?php

namespace Drupal\contacts_events\EventSubscriber;

use Drupal\commerce_paypal\Event\ExpressCheckoutRequestEvent;
use Drupal\commerce_paypal\Event\PayPalEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for adding additional information to payment requests.
 */
class PaymentDataSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    if (class_exists(PayPalEvents::class)) {
      $events[PayPalEvents::EXPRESS_CHECKOUT_REQUEST][] = ['paypalExpressCheckout'];
    }
    return $events;
  }

  /**
   * Adjust data for PayPal's express checkout.
   *
   * @param \Drupal\commerce_paypal\Event\ExpressCheckoutRequestEvent $event
   *   The express checkout event.
   */
  public function paypalExpressCheckout(ExpressCheckoutRequestEvent $event) {
    $order = $event->getOrder();
    if (!$order) {
      return;
    }

    $data = $event->getNvpData();
    if (!isset($data['EMAIL'])) {
      $data['EMAIL'] = $order->getEmail();
    }

    // First pull from the profile.
    if (($profile = $order->getBillingProfile()) && !$profile->get('address')->isEmpty()) {
      $address = $profile->get('address')->first()->getValue();
      $data += [
        'PAYMENTREQUEST_0_SHIPTOSTREET' => $address['address_line1'],
        'PAYMENTREQUEST_0_SHIPTOSTREET2' => $address['address_line2'],
        'PAYMENTREQUEST_0_SHIPTOCITY' => $address['locality'],
        'PAYMENTREQUEST_0_SHIPTOSTATE' => $address['administrative_area'],
        'PAYMENTREQUEST_0_SHIPTOZIP' => $address['postal_code'],
        'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $address['country_code'],
      ];
    }

    // Next from Contacts.
    $user = $order->getCustomer();
    if (!$user->isAnonymous()) {
      if (!isset($data['PAYMENTREQUEST_0_SHIPTONAME'])) {
        $data['PAYMENTREQUEST_0_SHIPTONAME'] = $user->getDisplayName();
      }

      if (!isset($data['PAYMENTREQUEST_0_SHIPTOPHONENUM'])) {
        /* @var \Drupal\profile\Entity\ProfileInterface $profile */
        if ($profile = $user->get('profile_crm_indiv')->entity) {
          if ($profile->hasField('crm_phone')) {
            $data['PAYMENTREQUEST_0_SHIPTOPHONENUM'] = $profile->get('crm_phone')->value;
          }
        }
      }
    }

    $event->setNvpData($data);
  }

}
