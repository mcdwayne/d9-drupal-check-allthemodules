<?php
/**
 * @file
 * Provide views data for our custom module_name.
 */
/**
 * Implements hook_views_data().
 */
function form_fields_site_area_views_data() {

  $data['views']['form_fields_site_area'] = array(
    'title' => t('Form Fields Site Area'),
    'help' => t('Provide replacment tokens for form fields to be displayed in Footer and Header.'),
    'area' => array(
      'id' => 'form_fields_site_area',
    ),
  );
  return $data;
}
