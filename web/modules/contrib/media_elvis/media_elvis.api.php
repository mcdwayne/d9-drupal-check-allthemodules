<?php
/**
 * @file
 * Api documentation for Media Elvis.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the mapping when a new entity is creaed.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object we are creating.
 * @param $data
 *   The data array we get from Elvis.
 *
 * @see \Drupal\media_elvis\Plugin\EntityBrowser\Widget\MediaElvis::prepareEntities
 */
function hook_media_elvis_field_mapping_alter(\Drupal\Core\Entity\EntityInterface $entity, $data) {
  if (isset($data->metadata->title)) {
    $entity->set('field_image_alt_text', $data->metadata->title, 255);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
