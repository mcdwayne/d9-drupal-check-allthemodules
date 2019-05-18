<?php
/**
 * @file
 * Contains theme functions for og_sm_user module.
 */

/**
 * Theme implementation for a user profile section.
 */
function theme_og_sm_user_page_section($variables) {
  $output = array();

  if (!empty($variables['label'])) {
    $output[] = array(
      '#theme' => 'html_tag',
      '#value' => $variables['label'],
      '#tag' => 'h2',
    );
  }
  foreach ($variables['elements'] as $element_name => $element) {
    $container = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'field field--profile',
        'id' => drupal_html_id($element_name),
      ),
    );
    if (isset($element['#label'])) {
      $container['label'] = array(
        '#theme' => 'html_tag',
        '#value' => $element['#label'],
        '#tag' => 'h3',
      );
    }
    $container['element'] = $element;
    $output[] = $container;
  }

  return render($output);
}
