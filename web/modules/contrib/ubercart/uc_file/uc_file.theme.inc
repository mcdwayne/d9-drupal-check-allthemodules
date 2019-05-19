<?php

/**
 * @file
 * Theme functions for the uc_file module.
 */

use Drupal\Core\Render\Element;

/**
 * Themes the download table at the user account page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_file_hook_user_file_downloads(array $variables) {
  $form = $variables['form'];

  $header = [
    ['data' => t('Remove')],
    ['data' => t('Filename')],
    ['data' => t('Expiration')],
    ['data' => t('Downloads')],
    ['data' => t('Addresses')],
  ];
  $rows = [];

  foreach (Element::children($form['file_download']) as $key) {

    if (!isset($form['file_download'][$key]['addresses_in'])) {
      continue;
    }

    $file_download = &$form['file_download'][$key];

    $rows[] = [
      'data' => [
        ['data' => $file_download['remove']],
        ['data' => $file_download['filename']],
        [
          'data' => [
            '#type' => 'container',
            '#prefix' => drupal_render($file_download['expires']) . ' <br />',
            'time' => [
              'polarity' => $file_download['time_polarity'],
              'quantity' => $file_download['time_quantity'],
              'granularity' => $file_download['time_granularity'],
            ],
          ],
          'class' => ['duration'],
        ],
        [
          'data' => [
            '#type' => 'container',
            '#markup' => drupal_render($file_download['downloads_in']) . '/',
            'limit' => $file_download['download_limit'],
          ],
          'class' => ['download-table-index'],
        ],
        [
          'data' => [
            '#type' => 'container',
            '#markup' => drupal_render($file_download['addresses_in']) . '/',
            'limit' => $file_download['address_limit'],
          ],
          'class' => ['download-table-index'],
        ],
      ],
      'class' => ['download-table-row'],
    ];
  }

  $build = [
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => ['id' => 'download-table'],
    '#empty' => t('No files can be downloaded by this user.'),
  ];

  return drupal_render($build);
}
