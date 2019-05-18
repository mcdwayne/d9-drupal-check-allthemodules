<?php

function drealty_admin_settings_page() {
  $item = menu_get_item();
  if ($content = system_admin_menu_block($item)) {
    $output = theme('admin_block_content', array('content' => $content));
  } else {
    $output = t('You do not have any administrative items.');
  }
  return $output;
}

function drealty_metadata_update_form($form, &$form_state, $conid) {

  $form['drealty_metadata_update'] = array(
    '#type' => 'checkbox',
    '#title' => 'Update Metadata for this connection?',
    '#description' => 'RETS Metadata will be updated to refresh the values stored in the database.',
    '#default_value' => variable_get('drealty_metadata_update', 0),
  );

  $form['actions'] = array('#type' => 'actions');

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#weight' => 40,
  );

  return $form;
}

/**
 * Submit handler.
 */
function drealty_metadata_update_form_submit($form, &$form_state) {
  $conid = $form_state['build_info']['args'][0];
  $dc = new drealtyConnection();
  $metadata = new drealtyMetaData();

  if($conid)
    $connection = $dc->FetchConnection($conid);

  if ($connection && $connection->active && $connection->status) {
    $mappings = $connection->ResourceMappings();

    foreach ($mappings as $mapping) {
      $resource = $metadata->FetchResource($mapping->rid);
      $classes = $connection->FetchClasses($resource);
      foreach ($classes as $key => $class) {
        $metadata->UpdateDrealtyMetadata($connection->conid, $resource, $class);
      }
    }
  }
}