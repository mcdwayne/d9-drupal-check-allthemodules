<?php

namespace Drupal\entity_pilot\Event\Subscriber;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_pilot\Event\CalculateDependenciesEvent;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to the calculate dependencies event.
 */
class MenuLinkContentDependencySubscriber implements EventSubscriberInterface {

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MenuLinkContentDependencySubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Adds dependencies for menu-link content entities.
   *
   * @param \Drupal\entity_pilot\Event\CalculateDependenciesEvent $event
   *   Calculate dependencies event.
   */
  public function calculateMenuLinkDependencies(CalculateDependenciesEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'menu_link_content') {
      return;
    }
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    $url = $entity->getUrlObject();
    $matches = [];
    $dependencies = $event->getDependencies();
    $tags = $event->getTags();
    try {
      if (preg_match('/^entity\.(.*)\.canonical$/', $url->getRouteName(), $matches)) {
        $entity_type_id = $matches[1];
        $params = $url->getRouteParameters();
        if (!isset($params[$entity_type_id])) {
          return;
        }
        $entity_id = $url->getRouteParameters()[$entity_type_id];
        try {
          if ($dependant = $this->entityTypeManager->getStorage($entity_type_id)
            ->load($entity_id)
          ) {
            // This menu-link links to a valid entity on the site, add it as a
            // dependency.
            $dependencies[$dependant->uuid()] = $dependant;
            if (($parent_id = $entity->getParentId()) && strpos($parent_id, PluginBase::DERIVATIVE_SEPARATOR) !== FALSE) {
              list($plugin_id, $parent_uuid) = explode(PluginBase::DERIVATIVE_SEPARATOR, $parent_id);
              if ($plugin_id === 'menu_link_content' && $parent_entity = $this->entityRepository->loadEntityByUuid('menu_link_content', $parent_uuid)) {
                if (!isset($dependencies[$parent_entity->uuid()]) && $parent_entity->uuid() !== $entity->uuid()) {
                  $dependencies[$parent_entity->uuid()] = $dependant;
                  $tags[] = sprintf('ep__%s__%s', $parent_entity->getEntityTypeId(), $parent_entity->id());
                  $child_event = new CalculateDependenciesEvent($parent_entity, $dependencies, $tags);
                  $this->calculateMenuLinkDependencies($child_event);
                  $dependencies += $child_event->getDependencies();
                  $tags = array_unique(array_merge($tags, $child_event->getTags()));
                  unset($dependencies[$entity->uuid()]);
                }
              }
            }
          }
        }
        catch (PluginNotFoundException $e) {
          // Entity-type no-longer exists on the site.
          return;
        }
      }
    }
    catch (\UnexpectedValueException $e) {
      // Not an internal URI.
      return;
    }
    $event->setDependencies($dependencies);
    $event->setTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityPilotEvents::CALCULATE_DEPENDENCIES => 'calculateMenuLinkDependencies',
    ];
  }

}
