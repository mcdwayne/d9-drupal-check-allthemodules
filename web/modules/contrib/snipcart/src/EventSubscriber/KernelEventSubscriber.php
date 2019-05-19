<?php

namespace Drupal\snipcart\EventSubscriber;

use Drupal\snipcart\SnipcartRequestValidationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener for kernel events.
 */
class KernelEventSubscriber implements EventSubscriberInterface {

  /** @var  \Drupal\snipcart\SnipcartRequestValidationServiceInterface */
  private $validationService;

  /**
   * KernelEventSubscriber constructor.
   * @param \Drupal\snipcart\SnipcartRequestValidationServiceInterface $validationService
   */
  public function __construct(SnipcartRequestValidationServiceInterface $validationService) {
    $this->validationService = $validationService;
  }


  /** {@inheritdoc} */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 50];
    return $events;
  }

  /**
   * Validate incoming Snipcart requests
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onRequest(GetResponseEvent $event) {
    if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST
      && preg_match('$^/snipcart$', $event->getRequest()->getPathInfo())) {
      if (!\Drupal::config('snipcart.settings')
          ->get('disable_request_validation') && !$this->validationService->validateRequest($event->getRequest())
      ) {
        throw new AccessDeniedHttpException('Unauthorized use');
      }
    }
  }

}
