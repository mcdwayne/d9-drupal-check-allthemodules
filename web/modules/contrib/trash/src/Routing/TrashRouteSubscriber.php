<?php

namespace Drupal\trash\Routing;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for content entity trash routes.
 */
class TrashRouteSubscriber extends RouteSubscriberBase {

  /**
   * The Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Constructs a TrashRouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->getModeratedEntityTypes() as $entity_type_id => $entity_type) {
      $route = $collection->get("entity.$entity_type_id.delete_form");
      if (!empty($route)) {
        $defaults = $route->getDefaults();
        unset($defaults['_entity_form']);
        $defaults['_controller'] = '\Drupal\trash\Controller\TrashDeleteController::trashEntity';
        $route->setDefaults($defaults);
        $route->setOption('parameters', [$entity_type_id => ['type' => 'entity:' . $entity_type_id]]);
        $route->setRequirement('_csrf_token', 'TRUE');
      }
    }
  }

  /**
   * Returns the list of Moderated content entity types.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   An array of entity objects indexed by their IDs. Returns an empty array
   *   if no matching entities are found.
   */
  protected function getModeratedEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    return array_filter($entity_types, function (EntityTypeInterface $entity_type) use ($entity_types) {
      return $this->moderationInformation->canModerateEntitiesOfEntityType($entity_type);
    });
  }

}
