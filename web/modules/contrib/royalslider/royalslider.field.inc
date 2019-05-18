<?php

/**
 * @file
 * Implement a royalslider field, based on the image module's image field.
 */

/**
 * Prepares variables for royalslider formatter templates.
 *
 * Default template: royalslider-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - items: An array of slider image objects.
 */
function template_preprocess_royalslider_formatter(&$variables) {
//  $variables['royalslider_items'] = $variables['items'];
//  $entity = $variables['entity'];
//  $variables['entity_type'] = $entity->getEntityType();
//  $variables['entity_id'] = $entity->uuid();
  kint($variables);
}