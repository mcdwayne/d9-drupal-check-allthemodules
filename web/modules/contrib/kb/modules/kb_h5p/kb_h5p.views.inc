<?php
/**
 * @file
 * Provide views data for add buttons.
 */

/**
 * Implements hook_views_data().
 */
function kb_h5p_views_data()
{
  $data['views']['kb_h5p_content_add_button_area'] = [
    'title' => t('Add KB H5P Content Button'),
    'help' => t('Displays a KB H5P Content add button.'),
    'area' => [
      'id' => 'kb_h5p_content_add_button_area',
    ],
  ];

  return $data;
}