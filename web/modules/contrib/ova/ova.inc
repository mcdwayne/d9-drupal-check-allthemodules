<?php

/**
 * @file
 * Open Video Annotation using template variables.
 */

 /**
 *  Implements template_preprocess_hook().
 */
function template_preprocess_ovaAudio(&$variables) {
  $annotation_settings = array();
  $entity = $variables['entity'];
  $resource_entity_id = $entity->id();
  $variables['#attached']['drupalSettings']['ova_annotation']['annotation_settings'] = $resource_entity_id;
}

/**
 *  Implements template_preprocess_hook().
 */
function template_preprocess_ovaVideo(&$variables) {
  $annotation_settings = array();
  $entity = $variables['entity'];
  $resource_entity_id = $entity->id();
  $variables['#attached']['drupalSettings']['ova_annotation']['annotation_settings'] = $resource_entity_id;
}
