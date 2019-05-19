<?php

namespace Drupal\trash\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks for trash module.
 */
class TrashLocalTasks extends DeriverBase implements ContainerDeriverInterface {

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
   * Constructs a TrashLocalTasks object.
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
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->getModeratedEntityTypes() as $entity_type_id => $entity_type) {
      $this->derivatives["trash_$entity_type_id"] = $base_plugin_definition;
      $this->derivatives["trash_$entity_type_id"]['title'] = $entity_type->get('label');
      $this->derivatives["trash_$entity_type_id"]['route_name'] = 'trash.entity_list';
      $this->derivatives["trash_$entity_type_id"]['parent_id'] = 'trash.default';
      $this->derivatives["trash_$entity_type_id"]['route_parameters'] = ['entity_type_id' => $entity_type_id];
    }
    return $this->derivatives;
  }

  /**
   * Returns the list of moderated entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]|null
   *   Returns the list of moderated entity types.
   */
  protected function getModeratedEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    return array_filter($entity_types, function (EntityTypeInterface $entity_type) use ($entity_types) {
      return $this->moderationInformation->canModerateEntitiesOfEntityType($entity_type);
    });
  }

}
