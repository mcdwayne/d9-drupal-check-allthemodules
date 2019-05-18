<?php

namespace Drupal\access_by_entity\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\access_by_entity\AccessByEntityStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AccessByEntityKernelEventSubscriber.
 *
 * @package Drupal\access_by_entity\EventSubscriber
 */
class AccessByEntityKernelEventSubscriber implements EventSubscriberInterface {

  /**
   * The access checker.
   *
   * @var \Drupal\access_by_entity\AccessCheckerInterface
   */
  private $accessByEntityStorage;

  /**
   * The core string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $translation;

  /**
   * The core string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $currentRouteMatch;

  /**
   * PermissionsByEntityKernelEventSubscriber constructor.
   *
   * @param \Drupal\access_by_entity\AccessByEntityStorageInterface $access_by_entity_storage
   *   Custom service check if the current user is allowed to access an entity.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The core string translator.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match_match
   *   The core RouteMatch.
   */
  public function __construct(
    AccessByEntityStorageInterface $access_by_entity_storage,
    TranslationInterface $translation,
    RouteMatchInterface $route_match_match
  ) {
    $this->accessByEntityStorage = $access_by_entity_storage;
    $this->translation = $translation;
    $this->currentRouteMatch = $route_match_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 5],
    ];
  }

  /**
   * Callback method for the KernelEvents::REQUEST event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event instance.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // Get the current request from the event.
    $request = $event->getRequest();
    $parameters = $this->currentRouteMatch->getRouteObject()->getOption('parameters');
    $route = $request->attributes->get('_route');
    if ($route == 'system.403') {
      return;
    }

    $entity_type_id = NULL;
    if ($parameters) {
      $entity_type_id = array_keys($parameters)[0];
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $request->attributes->get(
      $entity_type_id
    );

    /*
     * @todo
     * Maybe we can find better solution to get $op.
     */
    $op = NULL;
    if ($route !== "entity.$entity_type_id.canonical") {
      $op = $route;
      $op = str_replace('entity.node.', '', $op);
      $op = str_replace('_form', '', $op);
    }
    else {
      if ($route == "entity.$entity_type_id.canonical") {
        $op = 'view';
      }
    }
    // Check if the current user is allowed to access this entity.
    if ($entity && $entity instanceof EntityInterface && $op
      && !$this->accessByEntityStorage->isAccessAllowed($entity->id(), $entity->getEntityTypeId(), $op)
    ) {
      // If the current user is not allowed to access this entity,
      // we throw an AccessDeniedHttpException.
      throw new AccessDeniedHttpException(
        $this->translation->translate(
          'You are not allowed to %perm content of this entity type.',
          ['%perm' => $op]
        )
      );
    }
  }

}
