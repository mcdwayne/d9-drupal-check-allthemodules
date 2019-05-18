<?php
/**
 * @file
 * Provide views data for add buttons.
 */

/**
 * Implements hook_views_data().
 */
function kb_views_data()
{
  $data['views']['kb_category_add_button_area'] = [
    'title' => t('Add KB Category Button'),
    'help' => t('Displays a KB Category add button.'),
    'area' => [
      'id' => 'kb_category_add_button_area',
    ],
  ];
  $data['views']['kb_content_add_button_area'] = [
    'title' => t('Add KB Content Button'),
    'help' => t('Displays a KB Content add button.'),
    'area' => [
      'id' => 'kb_content_add_button_area',
    ],
  ];

  return $data;
}