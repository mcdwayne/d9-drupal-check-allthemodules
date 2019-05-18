<?php

namespace Drupal\booking\Plugin\Deriver;

use Drupal\rest\Plugin\Deriver\EntityDeriver;

/**
 * Provides a resource plugin definition for the Booking content type.
 *
 * @see \Drupal\rest\Plugin\Deriver\EntityDeriver
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class BookingDeriver extends EntityDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      // Add in the default plugin configuration and the resource type.

      $entity_type_id = 'node';
      $entity_type = new Drupal\Core\Entity\ContentEntityType;

      $this->derivatives[$entity_type_id] = array(
        'id' => 'entity:node:booking',
        'entity_type' => $entity_type_id,
        'serialization_class' => 'Drupal\Core\Entity\ContentEntityType',
        'label' => 'Booking',
      );

      $default_uris = array(
        'canonical' => "/entity/$entity_type_id/" . '{' . $entity_type_id . '}',
        'https://www.drupal.org/link-relations/create' => "/entity/$entity_type_id",
      );

      foreach ($default_uris as $link_relation => $default_uri) {
        // Check if there are link templates defined for the entity type and
        // use the path from the route instead of the default.
        if ($link_template = $entity_type->getLinkTemplate($link_relation)) {
          $this->derivatives[$entity_type_id]['uri_paths'][$link_relation] = $link_template;
        }
        else {
          $this->derivatives[$entity_type_id]['uri_paths'][$link_relation] = $default_uri;
        }
      }

      $this->derivatives[$entity_type_id] += $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
