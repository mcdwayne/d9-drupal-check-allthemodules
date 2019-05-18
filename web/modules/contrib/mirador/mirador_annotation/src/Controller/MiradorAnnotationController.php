<?php

/**
 * @file
 * Contains \Drupal\mirador_annotation\Controller\MiradorAnnotationController.
 */

namespace Drupal\mirador_annotation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for mirador annotations creation.
 */
class MiradorAnnotationController extends ControllerBase {

  /**
   * Page Callback: Search Annotation.
   */
  public function searchAnnotation($resource_entity_id) {
    $output = array();
    $output = $this->getAnnotation($resource_entity_id);
    return new JsonResponse($output);
  }

  /**
   * Returns the annotations list.
   */
  public function getAnnotation($resource_entity_id) {
    $annotations = array();
    // Load the mirador global settings.
    $config = \Drupal::config('mirador.settings');
    // Annotation settings.
    $annotation_entity = $config->get('annotation_entity');
    $annotation_text = $config->get('annotation_text');
    $annotation_viewport = $config->get('annotation_viewport');
    $annotation_image_entity = $config->get('annotation_image_entity');
    $annotation_image_resource = $config->get('annotation_resource');
    $annotation_resource_data = $config->get('annotation_data');

    // Fetch the annotation entity_ids.
     $annotation_ids = \Drupal::entityQuery($annotation_entity)
      ->condition($annotation_image_entity, $resource_entity_id)
      ->execute();

    // Load the annotations.
    $annotation_data = \Drupal::entityTypeManager()
      ->getStorage($annotation_entity)
      ->loadMultiple($annotation_ids);

    foreach ($annotation_data as $annotation) {
      $annotations[] = array(
        'data' => json_decode($annotation->$annotation_resource_data->value, TRUE),
        'id' => $annotation->nid->value,
        'text' => $annotation->$annotation_text->value,
      );
    }
    return $annotations;
  }

}
