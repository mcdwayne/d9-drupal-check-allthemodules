<?php

namespace Drupal\commerce_vl\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_vl\ViralLoopsIntegratorInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class ViralLoopsEventSubscriber implements EventSubscriberInterface {

  /**
   * The EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The ViralLoopsIntegrator service definition.
   *
   * @var \Drupal\commerce_vl\ViralLoopsIntegratorInterface
   */
  protected $viralLoopsIntegrator;

  /**
   * ViralLoopsEventSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_vl\ViralLoopsIntegratorInterface $viral_loops_integrator
   *   The service for Viral Loops integration.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ViralLoopsIntegratorInterface $viral_loops_integrator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->viralLoopsIntegrator = $viral_loops_integrator;
  }

  /**
   * Store Viral Loops referral code in $_SESSION.
   *
   * @param \Symfony\Component\HttpKernel\Event\FinishRequestEvent $event
   *   The Event to process.
   */
  public function onKernelFinishRequest(FinishRequestEvent $event) {
    $request = $event->getRequest();
    $referral_code = $request->query->get('referralCode');
    if ($referral_code) {
      $session = $request->getSession();
      if (!$session->get('vl_referral_code')) {
        $session->set('vl_referral_code', $referral_code);
      }
    }
  }

  /**
   * Called when the commerce_order.place.post_transition event is dispatched.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The Event to process.
   */
  public function orderCompleteHandler(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->viralLoopsIntegrator->handleOrderCompletion($order);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::FINISH_REQUEST][] = ['onKernelFinishRequest'];
    $events['commerce_order.place.post_transition'] = ['orderCompleteHandler'];
    return $events;
  }

}
