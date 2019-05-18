<?php

namespace Drupal\mailjet_stats\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class OrderCompleteSubscriber.
 *
 * @package Drupal\mailjet_stats
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
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['orderCompleteHandler'];

    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition
   * event is dispatched.
   *
   * @param WorkflowTransitionEvent $event
   */
  public function orderCompleteHandler(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    // Order items in the cart.
    $items = $order->getItems();


    if ($event->getToState()->getId() == 'completed') {

      if (isset($_SESSION['mailjet_campaign_id'])) {

        $campaign_mailjet_id = $_SESSION['mailjet_campaign_id'];

        $entity_manager = \Drupal::entityManager();
        $order2 = $entity_manager->getStorage('commerce_order')
          ->load($order->getOrderNumber());
        $order2->set('field_mailjet_campaign_id', $campaign_mailjet_id);
        $order2->save();

        $query = \Drupal::database()->select('mailjet_campign', 'mj');
        $query->addField('mj', 'campaign_id');
        $query->condition('mj.camp_id_mailjet', trim($_SESSION['mailjet_campaign_id']));
        $query->range(0, 1);
        $id = $query->execute()->fetchField();

        $campaign = $entity_manager->getStorage('campaign_entity')->load($id);
        $campaign->set('order_id', $order->getOrderNumber());
        $campaign->save();

        $order2->set('field_mailjet_campaign_nam', $campaign->get('name')->getValue()[0]['value']);
        $order2->save();

        unset($_SESSION['mailjet_campaign_id']);
      }

    }

  }

}

