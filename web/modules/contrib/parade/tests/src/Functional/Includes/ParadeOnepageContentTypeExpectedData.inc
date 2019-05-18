<?php

/**
 * @file
 * Expected data for the ParadeDemoContentTypeTest class.
 */

namespace Drupal\Tests\parade\Functional;

/**
 * Returns the data for the Parade Onepage content type.
 *
 * @return array
 *   Content type data.
 */
function get_parade_onepage_content_type_data() {
  $fields = [
    ['Machine name', 'parade_onepage_id', 'Machine name'],
    ['Menu', 'parade_onepage_menu', 'Link'],
    ['Sections', 'parade_onepage_sections', 'Entity reference revisions'],
  ];

  $views = [
    'default' => [
      ['Content'],
      ['Menu', '- Hidden -', 'Link'],
      ['Sections', '- Hidden -', 'Rendered entity'],
      ['Machine name', 'Above', 'Machine name'],
      ['Links', '', ''],
    ],
  ];

  $forms = [];

  return [
    'fields' => $fields,
    'views' => $views,
    'forms' => $forms,
  ];
}
