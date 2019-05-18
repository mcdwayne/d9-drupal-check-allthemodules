<?php

namespace Drupal\commerce_user_points\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class OrderCompleteSubscriber.
 *
 * @package Drupal\commerce_user_points
 */
class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   To intialize the entityTypeManager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Get Subscribed Events.
   *
   * @return array
   *   Return the event value.
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['orderCompleteHandler'];
    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition.
   *
   * @param Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   Handle the event.
   */
  public function orderCompleteHandler(WorkflowTransitionEvent $event) {

    // Get the order.
    $order = $event->getEntity();

    $dicountPriceValue = 0;
    $dicountPrice = 1;

    $pointsConfig = \Drupal::config('commerce_user_points.settings');

    $orderUid = $order->getCustomerId();
    $orderEmail = $order->getEmail();
    $orderSubtotal = $order->getSubtotalPrice();
    $orderTotal = $order->getTotalPrice();
    $dicountPrice = $pointsConfig->get('discout_price');
    $orderPointDiscount = $pointsConfig->get('order_point_discount');
    $dayPointDiscount = $pointsConfig->get('day_point_discount');
    $datePointDiscount = $pointsConfig->get('date_point_discount');

    // Check for the dicuount price and set the result.
    if ($dicountPrice == 1) {
      $dicountPriceValue = $orderSubtotal;
      $dicountPriceValue = $dicountPriceValue->getNumber();
    }
    if ($dicountPrice == 0) {
      $dicountPriceValue = $orderTotal;
      $dicountPriceValue = $dicountPriceValue->getNumber();
    }

    // Get discount percentage.
    $orderAppliedDiscount = $orderPointDiscount;

    // Update discount percentage if day is same.
    if (gmdate('N') == $dayPointDiscount) {
      $orderAppliedDiscount = $datePointDiscount;
    }

    // Built array to save "user_point" node.
    $arrNode = [
      'type' => 'user_points',
      'title' => "New order " . $order->getOrderNumber() . " for " . $orderEmail . ", UID: " . $orderUid,
      'uid' => $orderUid,
      'field_earned_points' => round(($dicountPriceValue * $orderAppliedDiscount) / 100),
      'field_points_acquisition_date' => gmdate('Y-m-d'),
      'field_point_status' => 1,
      'field_point_type' => 'shopping',
      'field_used_points' => 0,
      'field_validity_date' => gmdate('Y-m-d', strtotime('+1 years')),
    ];

    // Save new node.
    $nodeEntity = entity_create('node', $arrNode);
    $nodeEntity->save();
  }

}
