<?php

namespace Drupal\smart_content_segments\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\smart_content_segments\Entity\SmartSegment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides condition plugin definitions for segments.
 *
 * @see Drupal\smart_content_segments\Plugin\smart_content\Condition\Segment
 */
class SegmentDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SegmentDeriver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
    $this->derivatives = [];
    $query = $this->entityTypeManager->getStorage('smart_segment')->getQuery();
    if ($ids = $query->execute()) {
      foreach (SmartSegment::loadMultiple($ids) as $entity) {
        $this->derivatives[$entity->id()] = [
          'label' => $entity->label(),
        ] + $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

}
