<?php




/**
* @author Florian Wittmann, Martin Scholz
*/
function wisski_textanly_admin_list($form_state) {

  $form = array();
  $form['add_analyser'] = array(
    '#type' => 'fieldset',
    '#title' => t('Add analyser'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => 1200,
  );
  $form['add_analyser']['dl'] = array(
    '#type' => 'markup',
    '#prefix' => '<dl style="list-style-type:none;">',
    '#suffix' => '</dl>',
  );
  $types = wisski_textanly_get_types();
  uasort($types, function($a, $b) { return strcmp($a['title'], $b['title']); });
  foreach ($types as $type => $info) {
    if ($type == 'local') continue; // singleton!
    $form['add_analyser']['dl'][$type] = array(
      '#type' => 'markup',
      '#prefix' => '<li>',
      '#value' => '<dt>' . l($info['title'], "admin/settings/wisski/text/anly/$type/add") . '</dt><dd>' . $info['description'] . '</dd>',
      '#suffix' => '</li>',
    );
  }

  $form['add_profile'] = array(
    '#type' => 'fieldset',
    '#title' => t('Add profile'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#weight' => 1100
  );
  $form['add_profile']['profile_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#description' => t('A unique name. Max. 50 characters.'),
    '#maxlength' => 50,
  );
  $form['add_profile']['profile_submit'] = array(
    '#type' => 'submit',
    '#value' => 'Create Profile',
    '#submit' => array('wisski_textanly_admin_create_profile_submit'),
    '#validate' => array('wisski_textanly_admin_create_profile_validate'),
  );

  $profiles = variable_get('wisski_textanly_profiles', array('default'));
  sort($profiles);
  $profiles = array_flip($profiles);
  
  foreach ($profiles as $p => $k) {
    
    $form["profile_$k"] = array(
      '#theme' => 'wisski_textanly_profile_table',
      '#caption' => t('Profile %p', array('%p' => $p)),
      '#id' => $k,
      '#weight' => $k,
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );

  }
    
  $aps = wisski_textanly_get();
  $aps_list = array();
  foreach ($aps as $ap) {
    $aid = $ap->aid;
    
    $profile_table = "profile_" . $profiles[$ap->profile];

    $ops = l(t('Configure'), 'admin/settings/wisski/text/anly/' . $ap->aid . '/edit');
    $ops .= ' ' . l(t('Clone'), 'admin/settings/wisski/text/anly/' . $ap->aid . '/clone');
    $ops .= ' ' . l(t('Remove'), 'admin/settings/wisski/text/anly/' . $ap->aid . '/delete');

    $data = array(
        check_plain($ap->name),
        check_plain($types[$ap->type]['title']),
        $ops
        );

    $form[$profile_table]['rows']["ap_$aid"]['data'] = array(
      '#type' => 'value',
      '#value' => $data
    );

    //now create the weight form element. 
    //NOTE how we add the id into the element key
    $form[$profile_table]['rows']["ap_$aid"]['weight-'.$aid] = array(
      '#type' => 'weight',
      '#delta' => 100,
      '#default_value' => $ap->weight,
      //add a specific class in here - we need this later for tabledrag
      '#attributes' => array('class'=>'weight'),
    );

  }

  //Don't forget the submit button
  $form['buttons'] = array(
    '#prefix' => '<p>',
    '#suffix' => '</p>',
    '#weight' => 1000, 
    'submit' => array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ),
  );
  
  // make a link to the test page
  $form['test_page'] = array(
    '#prefix' => '<p>',
    '#suffix' => '</p>',
    '#weight' => 10000, 
    '#value' => l(t('Try out analyser configuration on the test page'), 'admin/settings/wisski/text/anly/test'),
  );


  return $form;

}


/**
* @author Martin Scholz
*/
function wisski_textanly_admin_create_profile_validate($form, $form_state) {
  if (trim($form_state['values']['profile_name']) == '') {
    form_set_error('profile_name', 'Profile name already exists.');
  }
  $profiles = variable_get('wisski_textanly_profiles', array('default'));
  if (in_array(trim($form_state['values']['profile_name']), $profiles)) {
    form_set_error('profile_name', 'Profile name already exists.');
  }
}


/**
* @author Martin Scholz
*/
function wisski_textanly_admin_create_profile_submit($form, $form_state) {
  $profiles = variable_get('wisski_textanly_profiles', array('default'));
  $profiles[] = trim($form_state['values']['profile_name']);
  variable_set('wisski_textanly_profiles', $profiles);
}


/**
* @author Florian Wittmann
*/
function wisski_textanly_admin_list_submit($form, &$form_state) {
  foreach ($form_state['values'] as $key => $data) {
    //we are only interested in weight elements
    if (substr($key, 0, 6) == 'weight') {
      //cunningly we have the DB id of the row in the element name
      $id = str_replace('weight-', '', $key);
      db_query("UPDATE {wisski_textanly_analyser} SET weight=%d WHERE aid=%d", $data, $id);
    }
  }
}


/**
* @author Florian Wittmann, Martin Scholz
*/
function theme_wisski_textanly_profile_table($form) {

  //loop through each "row" in the table array
  $table_rows = array();
  
  $caption = $form['#caption'];
  unset($form['#caption']);
  $table_id = $form['#id'];
  unset($form['#id']);

  if (isset($form['rows'])) {

    foreach ($form['rows'] as $eid => $row) {
      //we are only interested in numeric keys
      if (substr($eid, 0, 3) == 'ap_') { 
        $id = substr($eid, 3);
        $this_row = $row['data']['#value'];

        //Add the weight field to the row
        $this_row[] = drupal_render($row['weight-'.$id]);

        //Add the row to the array of rows
        $table_rows[] = array('data' => $this_row, 'class' => 'draggable');
        unset($form['rows'][$eid]);
      }
    }
  }

  //Make sure the header count matches the column count
  $header=array(
    "Name", "Type", "Operations", "Order"
  );

  $output = theme('table', $header, $table_rows, array('id' => 'textanly-table-' . $table_id), $caption);
  $output .= drupal_render($form);

  // Call add_tabledrag to add and setup the JS for us
  // The key thing here is the first param - the table ID
  // and the 4th param, the class of the form item which holds the weight
  drupal_add_tabledrag('textanly-table-' . $table_id, 'order', 'sibling', 'weight');     

  return $output;
}


/**
* @author Florian Wittmann, Martin Scholz
*/
function wisski_textanly_admin_edit(&$form_state, $aid = NULL, $type = '', $clone = FALSE) {
  
  $edit = $aid != NULL;

  if ($edit) {
    $ap = wisski_textanly_get($aid);
    $type = $ap->type;
  } else {
    
    // we get type from url arg, so check if it is valid
    $types = wisski_textanly_get_types();
    if (!is_string($type) || !isset($types[$type])) {
      drupal_set_message(t('Invalid type for analyser.'), 'error');
      return;
    }
    
    $ap = (object) array(
      'aid' => NULL,
      'name' => $types[$type]['title'],
      'profile' => 'default',
      'weight' => 0,
      'lang' => '',
      'type' => $type,
      'module' => $types[$type]['module'],
      'settings' => NULL,
    );
  }
  
  // on cloning we use the same settings but remove the id and change the name
  if ($clone) {
    $ap->aid = NULL;
    $ap->name = t('Copy of !n', array('!n' => $ap->name));
  }

  // set multipart, in case the endpoint settings section has a file field
  $form['#attributes'] = array('enctype' => "multipart/form-data");
  $form['#tree'] = TRUE;

  // store aid, module and settings in hidden fields
  $form['aid'] = array('#type' => 'value', '#value' => $ap->aid);
  $form['module'] = array('#type' => 'value', '#value' => $ap->module);
  $form['settings'] = array('#type' => 'value', '#value' => $voc->settings);
  $form['lang'] = array('#type' => 'value', '#value' => $ap->lang);
  $form['type'] = array('#type' => 'value', '#value' => $ap->type);

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $ap->name,
    '#required' => TRUE,
    '#weight' => 0,
  );
  
  $profiles = variable_get('wisski_textanly_profiles', array('default'));
  $profiles = array_combine($profiles, $profiles);
  $form['profile'] = array(
    '#type' => 'select',
    '#title' => t('Profile'),
    '#default_value' => $ap->profile,
    '#options' => $profiles,
    '#weight' => 10,
  );
  $form['settings'] = array(
    '#title' => t('@type dependent settings', array('@type' => (!$ap->type) ? t('Type') : $types[$ap->type]['title'])),
    '#description' => ($type) ? $types[$type]['description'] : t('No type selected. Please select a type.'),
    '#prefix' => '<div id="type-settings-wrapper"><p>',
    '#suffix' => '</p></div>',
    '#weight' => 30,
  );

  $form['settings'] += (array) module_invoke($ap->module, 'wisski_textanly', 'edit', $ap);

  $form['buttons'] = array(
    '#prefix' => '<div>',
    '#suffix' => '</div>',
    '#weight' => 50,
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#executes_submit_callback' => TRUE,
    '#validate' => array('wisski_textanly_admin_edit_validate'),
    '#submit' => array('wisski_textanly_admin_edit_submit'),
  );
  if ($edit) {
    $form['buttons']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('wisski_textanly_admin_edit_delete'),
    );
  }
  $form['buttons']['cancel'] = array(
    '#prefix' => '<a href="..">',
    '#value' => t('Cancel'),
    '#suffix' => '</a>',
  );

  return $form;

}


/**
* @author Florian Wittmann
*/
function wisski_textanly_admin_edit_validate($form, &$form_state) {
  $ap = (object) $form_state['values'];

  // name should be unique => check if name already exists
  $res = db_query("SELECT aid FROM {wisski_textanly_analyser} WHERE `name` = '%s'", $ap->name);
  $other_ap = db_fetch_object($res);
  if ($other_ap && $other_ap->aid != $ap->aid) {
    form_set_error('name', 'There already exists an analyser with that name. Please choose a different one.');
  }

}


/**
* @author Florian Wittmann
*/
function wisski_textanly_admin_edit_submit($form, &$form_state) {
  $ap = (object) $form_state['values'];

  $settings = module_invoke($ap->module, 'wisski_textanly', 'submit', $ap);
  if (!empty($settings)) $ap->settings = (array) $settings;

  wisski_textanly_save($ap);

  $form_state['redirect'] = 'admin/settings/wisski/text/anly';

}




/**
* @author Florian Wittmann
*/
function wisski_textanly_admin_edit_delete($form, &$form_state) {

  $form_state['redirect'] = 'admin/settings/wisski/text/anly/' . $form_state['values']['aid'] . '/delete';

}


/**
* @author Florian Wittmann
*/
function wisski_textanly_admin_delete($aid) {

  $ap = wisski_textanly_get($aid);

  return drupal_get_form(
    'wisski_helper_delete_template',
    $ap->name,
    "admin/settings/wisski/text/anly/$aid/edit", 
    "admin/settings/wisski/text/anly",
    'wisski_textanly_delete',
    $aid
  );

}




/**
* @author Martin Scholz
*/
function _wisski_textanly_admin_validate_numeric($e, $fs) {
  if (!preg_match('/^\d+(\.\d+)?$/', $e['#value'])) {
    form_error($e, t('Value must be numeric.'));
  }
}


