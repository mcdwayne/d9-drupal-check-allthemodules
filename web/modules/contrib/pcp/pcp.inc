<?php

use Drupal\field\FieldConfigInterface;

function pcp_get_complete_percentage_data($account) {

  // Get user profile field.
  $fields = array_filter(\Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user'), function ($field_definition) {
    return $field_definition instanceof FieldConfigInterface;
  });

  $pcp_data = [];
  $config   = \Drupal::config('pcp.settings');

  $field_order     = $config->get('field_order');
  $profile_fields  = $config->get('profile_fields');
  $hide_pcp_block  = $config->get('hide_block_on_complete');
  $open_field_link = $config->get('open_link');

  // Initialize all variable.
  $pcp_data['uid']             = "";
  $pcp_data['total']           = "";
  $pcp_data['open_link']       = "";
  $pcp_data['completed']       = "";
  $pcp_data['incomplete']      = "";
  $pcp_data['hide_pcp_block']  = "";
  $pcp_data['nextfield_name']  = "";
  $pcp_data['nextfield_title'] = "";
  $pcp_data['current_percent'] = "";
  $pcp_data['next_percent']    = "";
  $pcp_data['nextfield_name']  = "";

  if (!isset($profile_fields)) {
    $pcp_data['current_percent'] = 100;
    $pcp_data['next_percent']    = 0;
    return $pcp_data;
  }

  // Check which user profile field is filled.
  $empty_fields = $pcp_fields = [];
  foreach ($profile_fields as $field_name_key => $field_name) {
    if (strcmp($field_name_key, $field_name) == 0) {
      $enable = (!empty($account->{$field_name}->value) || !empty($account->{$field_name}->target_id)) ? 1 : 0;
      $pcp_fields[$field_name_key] = $enable;
      if ($enable == 0) {
        $empty_fields[$field_name_key] = $fields[$field_name]->label();
      }
    }
  }

  // Count filled, empty and total field.
  $fields_count       = is_array($pcp_fields) ? count($pcp_fields) : 0;
  $empty_fields_count = is_array($empty_fields) ? count($empty_fields) : 0;
  $completed_fields   = $fields_count - $empty_fields_count;

  $current_percent = 0;
  $next_percent    = 0;
  if ($fields_count != 0) {
    $current_percent = number_format(($completed_fields / $fields_count), 2) * 100;
    $next_percent    = number_format((($completed_fields + 1) / $fields_count), 2) * 100;
  }

  // Open profile field in new or same browser window.
  $open_field_link = $open_field_link == 0 ? '_self' : '_target';

  // Hide or Show when "Profile complete percentage" show 100%.
  $hide_pcp_block = $hide_pcp_block == 0 ? FALSE : TRUE;

  // Show random or same field, after refresh a browser window.
  reset($empty_fields);
  $next_field = $field_order == 0 ? array_rand($empty_fields) : key($empty_fields);
  $nextfield_title = isset($empty_fields[$next_field]) ? $empty_fields[$next_field] : "";

  $pcp_data['uid']             = $account->id();
  $pcp_data['total']           = $fields_count;
  $pcp_data['open_link']       = $open_field_link;
  $pcp_data['completed']       = $completed_fields;
  $pcp_data['incomplete']      = $empty_fields_count;
  $pcp_data['hide_pcp_block']  = $hide_pcp_block;
  $pcp_data['nextfield_name']  = $next_field;
  $pcp_data['nextfield_title'] = $nextfield_title;
  $pcp_data['current_percent'] = $current_percent;
  $pcp_data['next_percent']    = $next_percent;
  $pcp_data['nextfield_name']  = str_replace('_', '-', $next_field);

  return $pcp_data;
}
