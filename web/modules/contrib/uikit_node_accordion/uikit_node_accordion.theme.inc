<?php

/**
 * @file
 * Theme for Uikit Node Accordion views.
 */

/**
 * Prepares variables for views uikit node accordion templates.
 *
 * Default template: views-view-uikit-node-accordion.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 *   - options: An array of options. Each option contains:
 *     - node_title: Field to be used as node title.
 *     - node_image: Field to be used as node image.
 *     - node_created: Field to be used as node creation date.
 *     - node_summary: Field to be used as node summary.
 *     - node_body: Field to be used as node body.
 *     - node_link: Field to be used as node link.
 */
function template_preprocess_views_view_uikit_node_accordion(array &$variables) {

  $view = $variables['view'];
  $rows = $variables['rows'];

  // Prepare the node accordion.
  $node_title = $view->style_plugin->options['node_title'];
  $node_image = $view->style_plugin->options['node_image'];
  $node_created = $view->style_plugin->options['node_created'];
  $node_summary = $view->style_plugin->options['node_summary'];
  $node_body = $view->style_plugin->options['node_body'];
  $node_link = $view->style_plugin->options['node_link'];

  foreach ($rows as $id => $row) {
    $variables['rows'][$id]['node_title'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_title],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['node_image'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_image],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['node_created'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_created],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['node_summary'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_summary],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['node_body'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_body],
      '#row' => $row['#row'],
    ];
    $variables['rows'][$id]['node_link'] = [
      '#theme' => 'views_view_field',
      '#view' => $view,
      '#field' => $view->field[$node_link],
      '#row' => $row['#row'],
    ];
  }

}
