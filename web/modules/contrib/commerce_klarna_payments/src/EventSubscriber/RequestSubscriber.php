<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\EventSubscriber;

use Drupal\commerce_klarna_payments\Event\Events;
use Drupal\commerce_klarna_payments\Event\RequestEvent;
use Drupal\commerce_klarna_payments\Klarna\Data\Order\CreateCaptureInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface;
use Drupal\commerce_klarna_payments\Klarna\Service\Payment\RequestBuilder;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Gathers the session data for Klarna order.
 */
final class RequestSubscriber implements EventSubscriberInterface {

  protected $builder;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Service\Payment\RequestBuilder $builder
   *   The request builder.
   */
  public function __construct(RequestBuilder $builder) {
    $this->builder = $builder;
  }

  /**
   * Builds request for given type and order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The type.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface
   *   The request.
   */
  private function buildRequest(OrderInterface $order, string $type) : RequestInterface {
    $request = $this->builder
      ->withOrder($order)
      ->generateRequest($type);

    return $request;
  }

  /**
   * Responds to RequestEvent.
   *
   * @param \Drupal\commerce_klarna_payments\Event\RequestEvent $event
   *   The event to respond to.
   */
  public function onCaptureCreate(RequestEvent $event) {
    if (!$event->getRequest() instanceof CreateCaptureInterface) {
      $request = $this->buildRequest($event->getOrder(), 'capture');

      $event->setRequest($request);
    }
  }

  /**
   * Responds to RequestEvent.
   *
   * @param \Drupal\commerce_klarna_payments\Event\RequestEvent $event
   *   The event to respond to.
   */
  public function onOrderCreate(RequestEvent $event) {
    if (!$event->getRequest() instanceof RequestInterface) {
      $request = $this->buildRequest($event->getOrder(), 'place');

      $event->setRequest($request);
    }
  }

  /**
   * Responds to RequestEvent.
   *
   * @param \Drupal\commerce_klarna_payments\Event\RequestEvent $event
   *   The event to respond to.
   */
  public function onSessionCreate(RequestEvent $event) {
    if (!$event->getRequest() instanceof RequestInterface) {
      /** @var \Drupal\commerce_klarna_payments\Klarna\Request\Payment\Request $request */
      $request = $this->buildRequest($event->getOrder(), 'create');

      $event->setRequest($request);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[Events::SESSION_CREATE] = ['onSessionCreate'];
    $events[Events::ORDER_CREATE] = ['onOrderCreate'];
    $events[Events::CAPTURE_CREATE] = ['onCaptureCreate'];
    return $events;
  }

}
