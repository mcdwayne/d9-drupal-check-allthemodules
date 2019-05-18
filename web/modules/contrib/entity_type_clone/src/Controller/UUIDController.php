<?php

namespace Drupal\entity_type_clone\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Class UUIDController.
 *
 * @package Drupal\entity_type_clone\Controller
 */
class UUIDController extends ControllerBase {

  public function uuidGet() {
    $uuid = [];
    $entity_type_definations = \Drupal::entityTypeManager()->getDefinitions();
    /* @var $definition \Drupal\Core\Entity\EntityTypeInterface */
    foreach ($entity_type_definations as $definition) {
      if ($definition instanceof ContentEntityType) {
        $content_types = \Drupal::entityManager()->getBundleInfo($definition->id());
        $entity_type = $definition->getBundleEntityType();
        if ($entity_type && $content_types) {
          foreach ($content_types as $type_id => $type) {
            $uuid[$entity_type][$type_id] = \Drupal::entityTypeManager()->getStorage($entity_type)->load($type_id)->uuid();
          }
        }
      }
    }
    return $uuid;
  }

}
