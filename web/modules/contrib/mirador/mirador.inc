<?php

/**
 * @file
 * Mirador theme functions.
 */

use Drupal\Core\Template\Attribute;


/**
 * Prepares variables for mirador formatter templates.
 *
 * Default template: mirador-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - entity: An entity object.
 *   - settings: Formatter settings array.
 */
function template_preprocess_mirador_formatter(&$variables) {
  $annotation_settings = array();
  $perform_annotation = FALSE;
  global $base_url;
  $user = \Drupal::currentUser();
  $uid = \Drupal::currentUser()->id();
  $item = $variables['item'];
  $entity = $variables['entity'];
  $settings = $variables['settings'];
  // Load the mirador global settings.
  $config = \Drupal::config('mirador.settings');

  // Reset the $variables parameter.
  $variables = array();

  $classes_array = array('mirador');

  // Get the entity type.
  $entity_type_id = $entity->getEntityTypeId();

  // Build the gallery id.
  $id = $entity->id();
  $field_name = $item->getParent()->getName();
  $mirador_viewer_id = 'mirador-viewer-' . $id;

  // Generate manifest uri and pass to jquery.
  $manifest_uri = $base_url . '/mirador/manifest/' . $entity_type_id . '/' . $field_name . '/' . $id;
  $variables['#attached']['drupalSettings']['init']['entity']['manifest_uri'] = $manifest_uri;
  $variables['#attached']['drupalSettings']['init']['entity']['viewer_id'] = $mirador_viewer_id;
  $variables['#attached']['drupalSettings']['init']['entity']['entity_id'] = $id;

  // Annotation settings.
  $annotation_settings['annotation_entity'] = $config->get('annotation_entity');
  $annotation_settings['annotation_entity_bundle'] = $config->get('annotation_bundle');
  $annotation_settings['annotation_text'] = $config->get('annotation_text');
  $annotation_settings['annotation_viewport'] = $config->get('annotation_viewport');
  $annotation_settings['annotation_image_entity'] = $config->get('annotation_image_entity');
  $annotation_settings['annotation_owner'] = $config->get('annotation_owner');
  $annotation_settings['annotation_resource'] = $config->get('annotation_resource');
  $annotation_settings['annotation_data'] = $config->get('annotation_data');

  // Annotation endpoint settings.
  $annotation_settings['annotation_create_uri'] = $config->get('annotation_create');
  $annotation_settings['annotation_create_method'] = $config->get('annotation_create_method');
  $annotation_settings['annotation_search_uri'] = $config->get('annotation_search');
  $annotation_settings['annotation_search_uri'] = str_replace("{resource_entity_id}", $id, $annotation_settings['annotation_search_uri']);
  $annotation_settings['annotation_search_method'] = $config->get('annotation_search_method');
  $annotation_settings['annotation_update_uri'] = $config->get('annotation_update');
  $annotation_settings['annotation_update_method'] = $config->get('annotation_update_method');
  $annotation_settings['annotation_delete_uri'] = $config->get('annotation_delete');
  $annotation_settings['annotation_delete_method'] = $config->get('annotation_delete_method');
  $annotation_settings['type_url'] = $base_url . '/rest/type/' . $config->get('annotation_entity') . '/' . $config->get('annotation_bundle');
  $annotation_settings['endpoint'] = $config->get('endpoint');
  $annotation_settings['token_url'] = NULL;
  // If rest endpoint is enabled, then use the rest
  // token for user authentication.
  if ($config->get('endpoint') == "rest_endpoint") {
    $annotation_settings['token_url'] = $base_url . '/rest/session/token';
  }

  if ($user->hasPermission('perform image annotations')) {
    $perform_annotation = TRUE;
  }
  // Pass the annotation settings to jquery.
  $variables['#attached']['drupalSettings']['init']['entity']['user_id'] = $uid;
  $variables['#attached']['drupalSettings']['init']['token_url'] = $annotation_settings['token_url'];
  $variables['#attached']['drupalSettings']['init']['annotation_settings'] = json_encode($annotation_settings);
  $variables['#attached']['drupalSettings']['init']['perform_annotation'] = $perform_annotation;
  $variables['#attached']['drupalSettings']['init']['endpoint'] = $config->get('endpoint');

  $variables['attributes'] = new Attribute();
  $variables['attributes']['class'] = $classes_array;
  $variables['attributes']['id'] = $mirador_viewer_id;
}
