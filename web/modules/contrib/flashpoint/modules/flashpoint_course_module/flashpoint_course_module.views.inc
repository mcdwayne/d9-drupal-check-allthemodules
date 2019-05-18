<?php

/**
 * @file
 * Provide views data for flashpoint_course_module.
 */

/**
 * Implements hook_views_data().
 */
function flashpoint_course_module_views_data() {
  $data['flashpoint_course_module']['flashpoint_course_module_rendered'] = [
    'title' => t('Render Course Module'),
    'help' => t('Renders the course module and its content.'),
    'field' => [
      'id' => 'flashpoint_course_module_rendered',
    ],
  ];

  return $data;
}