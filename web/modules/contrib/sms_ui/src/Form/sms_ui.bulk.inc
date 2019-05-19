<?php
  
/*
 * Update the form with bulk compose addons
 */

/**
 * Page callback to send back selected bulklist to javascript
 */
function _sms_ui_load_bulklist_js() {
  $cached_form_state = array();
  $files = array();

  // Load the form from the Form API cache.
  if (!($cached_form = \Drupal::formBuilder()->getCache($_POST['form_build_id'], $cached_form_state))) {
    form_set_error('form_token', t('Validation error, please try again. If this error persists, please contact the site administrator.'));
    $output = _theme('status_messages');
    print drupal_to_js(array('status' => TRUE, 'data' => $output));
    exit();
  }
  
  $form_state = array('values' => $_POST);

  // Update cached form with new numbers from bulklist
  $cached_form['left_pane']['number_container']['number']['#value'] = implode("\n", _sms_ui_bulklist_load($_POST['bulklist']));

  // Rebuild form
  $form = form_builder($_POST['form_id'], $cached_form, $form_state);

  // Render message text area
  $output = drupal_render($form['left_pane']['number_container']['number']);
  print drupal_to_js(array('status' => TRUE, 'data' => $output));;
  exit();
}

/*
 * Get available templates
 */
function _sms_ui_get_templates() {
  if (\Drupal::moduleHandler()->moduleExists('sms_ui_templates')) {
    return sms_ui_templates_get_template_list();
  }
  else {
    return array();
  }
}

/**
 * Load phone numbers of a particular category (used for bulk lists)
 */
function _sms_ui_bulklist_load($category) {
	$user = \Drupal::currentUser();
	$result = db_query("SELECT * FROM {sms_ui_number} WHERE category = '%s' AND uid = %d AND isbulklist = 1", $category, $user->uid);
	$numbers = array();
	while ($number = db_fetch_object($result)) {
		$numbers[$number->ctid] = check_plain($number->mobile);
	}
	return $numbers;
}

