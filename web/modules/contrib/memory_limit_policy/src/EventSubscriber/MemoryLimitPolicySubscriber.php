<?php

namespace Drupal\memory_limit_policy\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\memory_limit_policy\Entity\MemoryLimitPolicy;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MemoryLimitPolicySubscriber.
 *
 * @package Drupal\memory_limit_policy
 */
class MemoryLimitPolicySubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new MemoryLimitPolicySubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config factory service.
   */
  public function __construct(EntityTypeManager $entityTypeManager,
                              ConfigFactoryInterface $config) {
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    $policies = $this->entityTypeManager->getStorage('memory_limit_policy')->loadByProperties(['status' => TRUE]);

    // Sort policies by weight.
    uasort($policies, function (MemoryLimitPolicy $a, MemoryLimitPolicy $b) {
      if ($a->getWeight() == $b->getWeight()) {
        return 0;
      }
      return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
    });

    $request->attributes->set('_memory_limit_policy_override', FALSE);

    /** @var \Drupal\memory_limit_policy\Entity\MemoryLimitPolicy $policy */
    foreach ($policies as $policy) {
      if ($policy->evaluate()) {
        ini_set('memory_limit', $policy->getMemory());
        $request->attributes->set('_memory_limit_policy_override', TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onResponse(FilterResponseEvent $event) {
    $request = $event->getRequest();

    $settings = $this->config->get('memory_limit_policy.settings');

    if ($settings->get('header')) {
      $response = $event->getResponse();
      $response->headers->set(
        'memory_limit',
        ini_get('memory_limit')
      );
      $response->headers->set(
        'memory_limit_policy_override',
        (int) $request->attributes->get('_memory_limit_policy_override')
      );

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // High priority for this subscriber to execute it soon enough.
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
