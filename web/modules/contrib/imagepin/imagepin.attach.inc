<?php

/**
 * @file
 * Imagepin attachment implementation.
 */

use Drupal\Component\Utility\Crypt;

/**
 * Attaches pins with their widgets on the given image field.
 *
 * @param array &$element
 *   An associative array containing:
 *    - '#object' - The entity the field item belongs to.
 *    - '#field_name' - The field name as string.
 *    - '#view_mode' - The view mode the entity is being rendered.
 * @param array &$items
 *   The items to be viewed.
 */
function imagepin_attach(array &$element, array &$items) {
  $entity = $element['#object'];
  $field_name = $element['#field_name'];
  $view_mode = $element['#view_mode'];

  $enabled_view_modes = \Drupal::service('entity_display.repository')
    ->getViewModeOptionsByBundle($entity->getEntityTypeId(), $entity->bundle());
  if (empty($enabled_view_modes[$view_mode])) {
    $view_mode = 'default';
  }

  $view_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  $settings_content = $view_storage->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $view_mode)->get('content');
  if (empty($settings_content[$field_name]['third_party_settings']['imagepin']['pinable'])) {
    return;
  }

  $imagepin_settings = $settings_content[$field_name]['third_party_settings']['imagepin'];

  $widget_repository = \Drupal::service('imagepin.widget_repository');
  $widgets = $widget_repository->loadForEntityFieldView($entity, $field_name, $view_mode);
  if (empty($widgets)) {
    return;
  }

  foreach ($items as &$item) {
    $item['content']['#imagepin'] = [
      'widgets' => $widgets,
      'settings' => $imagepin_settings,
    ];
    $item['content']['#pre_render'][] = 'imagepin_add_widgets_element';
    $item['content']['#post_render'][] = 'imagepin_add_widgets_markup';
  }
}

/**
 * Adds the imagepin widgets element on the given element for an image item.
 *
 * @see imagepin_attach().
 */
function imagepin_add_widgets_element(array $element) {
  if (!empty($element['#item']) && !empty($element['#imagepin'])) {
    $image_fid = $element['#item']->get('target_id')->getValue();
    if (!empty($element['#imagepin']['widgets'][$image_fid])) {
      $attach_id = Crypt::randomBytesBase64(2);
      $element['#item_attributes']['data-imagepin-attach-from'] = $attach_id;
      $element['#imagepin_widgets'] = [
        '#theme' => 'imagepin_widgets',
        '#widgets' => $element['#imagepin']['widgets'][$image_fid],
        '#attach_id' => $attach_id,
        '#breakpoint' => $element['#imagepin']['settings']['breakpoint'],
      ];
    }
    unset($element['#imagepin']);
  }
  return $element;
}

/**
 * Adds the markup for imagepin widgets on an element.
 *
 * @see imagepin_attach().
 */
function imagepin_add_widgets_markup($markup, $element) {
  $widgets_html = render($element['#imagepin_widgets']);
  return $markup . $widgets_html;
}
