<?php

/**
 * @file
 * Theme for Uikit Slideshow views.
 */

/**
 * Prepares variables for views uikit slideshow templates.
 *
 * Default template: views-view-uikit-slideshow.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 *   - options: An array of options. Each option contains:
 *     - slide_image: Field to be used as slide image
 *     - slide_title: Field to be used as slide title
 *     - slide_body: Field to be used as slide body
 *     - node_link: Field to be used as link to node
 *     - thumbnail: Field to be used as navigation thumbnail image.
 */
function template_preprocess_views_view_uikit_slideshow(array &$variables) {

  $view = $variables['view'];
  $rows = $variables['rows'];

  // Prepare the slideshow slides.
  $slide_image = $view->style_plugin->options['slide_image'];
  $slide_title = $view->style_plugin->options['slide_title'];
  $slide_body = $view->style_plugin->options['slide_body'];
  $node_link = $view->style_plugin->options['node_link'];
  $thumbnail = $view->style_plugin->options['thumbnail'];

  foreach ($rows as $id => $row) {
    $variables['rows'][$id]['slide_image'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$slide_image],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['slide_title'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$slide_title],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['slide_body'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$slide_body],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['node_link'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_link],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['thumbnail'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$thumbnail],
      '#row' => $row['#row'],
    ];
  }

}
