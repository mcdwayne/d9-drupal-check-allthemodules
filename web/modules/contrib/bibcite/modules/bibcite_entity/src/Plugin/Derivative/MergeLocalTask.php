<?php

namespace Drupal\bibcite_entity\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic tabs based on available formats.
 */
class MergeLocalTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DynamicLocalTasks.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /* @var \Drupal\Core\Entity\EntityTypeInterface $entity_type  */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      if ($entity_type->hasLinkTemplate('bibcite-merge-form')) {
        $this->derivatives["entity.$entity_type_id.bibcite_merge_form"] = [
          'route_name' => "entity.$entity_type_id.bibcite_merge_form",
          'base_route' => "entity.$entity_type_id.canonical",
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
