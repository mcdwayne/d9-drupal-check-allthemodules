<?php

namespace Drupal\entity_list\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListBlock.
 *
 * @package Drupal\entity_list\Plugin\Derivative
 */
class EntityListBlock extends DeriverBase implements ContainerDeriverInterface {

  protected $entityTypeManager;

  /**
   * EntityListBlock constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates a new class instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the fetcher.
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   *
   * @return static
   *   Returns an instance of this fetcher.
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
    foreach ($this->entityTypeManager->getStorage('entity_list')
               ->loadMultiple() as $list => $entity) {
      $this->derivatives[$list] = $base_plugin_definition;
      $this->derivatives[$list]['admin_label'] = $entity->label();
      $this->derivatives[$list]['config_dependencies']['config'] = [$entity->getConfigDependencyName()];
      $this->derivatives[$list]['entity_list_id'] = $entity->id();
    }
    return $this->derivatives;
  }

}
