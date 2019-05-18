<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */
namespace Drupal\commerce_multisafepay\EventSubscriber;

use Drupal\commerce_multisafepay\API\Client;
use Drupal\commerce_multisafepay\Helpers\ApiHelper;
use Drupal\commerce_multisafepay\Helpers\GatewayHelper;
use Drupal\commerce_multisafepay\Helpers\OrderHelper;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderFulfillmentSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;
    
    protected $entityTypeManager;
    protected $mspGatewayHelper;
    protected $mspOrderHelper;
    protected $mspApiHelper;
    protected $mspClient;


    /**
     * OrderFulfillmentSubscriber constructor.
     * @param EntityTypeManager $entity_type_manager
     */
    public function __construct(EntityTypeManager $entity_type_manager) {
        $this->entityTypeManager = $entity_type_manager;
        $this->mspGatewayHelper = new GatewayHelper();
        $this->mspOrderHelper = new OrderHelper();
        $this->mspApiHelper = new ApiHelper();
        $this->mspClient = new Client();
    }

    /**
     * Look for events and send them the function
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        //Look for event and send them the function with priority
        $events = [
            'commerce_order.fulfill.post_transition' => ['sendPatchRequest', -100],
        ];
        return $events;
    }

    /**
     * Send the patch request
     *
     * @param WorkflowTransitionEvent $event
     * @throws \Drupal\Core\TypedData\Exception\MissingDataException
     */
    public function sendPatchRequest(WorkflowTransitionEvent $event)
    {
        $order = $event->getEntity();
        //set the mode of the gateway
        $mode = $this->mspGatewayHelper->getGatewayMode($order);

        $gatewayId = $order->get('payment_gateway')->first(
        )->entity->getPluginId();

        //If MSP order and order has shipments, then send patch request
        if($this->mspGatewayHelper->isMspGateway($gatewayId) && $this->mspOrderHelper->orderHasShipments($order)){

            //Get data of the shipment
            $shipments = $order->get('shipments')->referencedEntities();
            $first_shipment = reset($shipments);
            $trackTrace = $first_shipment->getTrackingCode();

            //Set data of the shipment
            $data = [
                "tracktrace_code" => $trackTrace,
                "carrier" => null,
                "ship_date" => date('Y-m-d H:i:s'),
                "reason" => "Shipped"
            ];

            $this->mspApiHelper->setApiSettings($this->mspClient, $mode);
            $this->mspClient->orders->patch($data, "orders/{$order->id()}");
        }
    }
}