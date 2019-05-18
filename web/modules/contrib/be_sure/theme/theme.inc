<?php
/**
 * @file
 * Theme function of Be sure module.
 */

use Drupal\Core\Url;

/**
 * Process variables for be-sure-element.tpl.php.
 */
function template_preprocess_be_sure_element(&$variables) {
  $percent = $variables['status'];

  $progress_bar = [
    '#theme' => 'be_sure_progress_bar',
    '#percent' => $percent,
    '#attributes' => [
      'class' => [
        $percent > 25 ? ($percent > 75 ? 'bes-success' : 'bes-normal') : 'bes-warning',
        'progress__bar',
        'filled',
      ],
      'style' => [
        'width' => "{$percent}%",
      ]
    ],
    '#message' => t(
      '@passed/@total issues resolved',
      [
        '@passed' => $variables['passed'],
        '@total' => count($variables['items'])
      ]
    ),
  ];

  $variables['status'] = render($progress_bar);
}

/**
 * Process variables for be-sure-multiple.tpl.php.
 */
function template_preprocess_be_sure_multiple(&$variables) {
  foreach ($variables['titles'] as $id => $title) {
    $variables['titles'][$id] = $title;
    // We cannot create empty link. It's related to https://www.drupal.org/node/1543750
    $variables['titles'][$id] = \Drupal::l($title, new Url('<front>', [], [
      'external' => TRUE,
      'fragment' => $id,
    ]));
  }

  $variables['first_title'] = array_shift($variables['titles']);
  $variables['first_element_id'] = key($variables['elements']);
  $variables['first_element'] = array_shift($variables['elements']);
}
