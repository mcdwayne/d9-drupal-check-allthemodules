<?php

namespace Drupal\commerce_postcode_delivery\EventSubscriber;

use Drupal\Core\Render\RenderContext;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Like sending an email notification for a shipment method on order complete state.
 */
class ExtraFeatures implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['sendEmailNotifications'];

    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition event is dispatched.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   WorkflowTransitionEvent object.
   */
  public function sendEmailNotifications(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    if ($order->getState()->getValue()['value'] == 'completed' && $order->hasField('shipments')) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      foreach ($order->get('shipments')->referencedEntities() as $shipment) {
        $shipping_method = $shipment->getShippingMethod();
        $shipping_method_conf = $shipping_method->getPlugin()->getConfiguration();
        $to = $shipping_method_conf['send_email_to'];

        if (!empty($to)) {
          $shipping_method_name = $shipping_method->getName();

          $params = [
            'headers' => [
              'Content-Type' => 'text/html',
            ],
            'from' => $order->getStore()->getEmail(),
            'subject' => t('@name. Order #@number', [
              '@name' => $shipping_method_name,
              '@number' => $order->getOrderNumber(),
            ]),
            'order' => $order,
          ];

          $billing_profile = $order->getBillingProfile();
          $shipping_profile = $shipment->getShippingProfile();
          $billing_information = \Drupal::entityTypeManager()->getViewBuilder('profile')->view($billing_profile);
          $shipping_information = \Drupal::entityTypeManager()->getViewBuilder('profile')->view($shipping_profile);

          $build = [
            '#theme' => 'commerce_postcode_delivery_email_notification',
            '#order_entity' => $order,
            '#shipping_method_name' => $shipping_method_name,
            '#billing_information' => $billing_information,
            '#shipping_information' => $shipping_information,
          ];

          $langcode = \Drupal::currentUser()->getPreferredLangcode();
          $renderer = \Drupal::service('renderer');
          $params['body'] = $renderer->executeInRenderContext(new RenderContext(), function () use ($build, $renderer) {
            return $renderer->render($build);
          });

          \Drupal::service('plugin.manager.mail')->mail('commerce_postcode_delivery', 'notify_shipping_order', $to, $langcode, $params);
        }
      }
    }
  }

}
