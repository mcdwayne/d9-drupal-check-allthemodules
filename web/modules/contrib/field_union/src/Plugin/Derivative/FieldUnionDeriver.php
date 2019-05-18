<?php

namespace Drupal\field_union\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for deriving field union field type plugins.
 */
class FieldUnionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldUnionDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('field_union')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\field_union\Entity\FieldUnionInterface $union */
    foreach ($this->entityTypeManager->getStorage('field_union')->loadMultiple() as $id => $union) {
      $this->derivatives[$id] = [
        'label' => $union->label(),
        'description' => $union->getDescription(),
        'constraints' => ["FieldUnionConstraint" => ['fields' => $union->getFields()]],
        'fields' => $union->getFields(),
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
