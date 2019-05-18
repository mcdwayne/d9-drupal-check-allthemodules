<?php

/**
 * @file
 *   Provides the configuration page for Shorten URLs Custom Services.
 */

/**
 * Themes the configuration page.
 */
function theme_shorten_cs_admin() {
  $form = \Drupal::formBuilder()->getForm('shorten_cs_add_form');
  return shorten_cs_services_table() . \Drupal::service("renderer")->render($form);
}

/**
 * Returns HTML representing the shorten service edit form.
 */
function shorten_cs_edit_form($service) {
  $form = \Drupal::formBuilder()->getForm('shorten_cs_edit', $service);
  return \Drupal::service("renderer")->render($form);
}

/**
 * Builds the form to edit a custom service.
 */
function shorten_cs_edit($form, &$form_state, $service) {
  $form = shorten_cs_add_form($form, $form_state);
  foreach (array('name', 'url', 'type', 'tag') as $key) {
    $form[$key]['#default_value'] = $service->{$key};
  }
  $form['sid'] = array(
    '#type' => 'value',
    '#value' => $service->sid,
  );
  $form['old_name'] = array(
    '#type' => 'value',
    '#value' => $service->name,
  );
  return $form;
}

/**
 * Validates the form to edit a custom service.
 */
function shorten_cs_edit_validate($form, $form_state) {
  $v = $form_state['values'];
  if (($v['type'] == 'xml' || $v['type'] == 'json') && empty($v['tag'])) {
    form_set_error('type', t('An XML tag or JSON key is required for services with a response type of XML or JSON.'));
  }
  $exists = db_query("SELECT COUNT(sid) FROM {shorten_cs} WHERE name = :name AND sid <> :sid", array(':name' => $v['name'], ':sid' => $v['sid']))->fetchField();
  if ($exists > 0) {
    form_set_error('name', t('A service with that name already exists.'));
  }
  else {
    $all_services = \Drupal::moduleHandler()->invokeAll('shorten_service');
    $all_services['none'] = t('None');
    foreach ($all_services as $key => $value) {
      if ($key == $v['name']) {
        form_set_error('name', t('A service with that name already exists.'));
        break;
      }
    }
  }
}

/**
 * Submits the form to edit a service.
 */
function shorten_cs_edit_submit($form, $form_state) {
  $v = $form_state['values'];
  $record = array();
  foreach (array('name', 'url', 'type', 'tag', 'sid') as $key) {
    $record[$key] = $v[$key];
  }
  \Drupal::database()->merge('shorten_cs')->fields($record)->key(['sid'])->execute();
  // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// if ($v['old_name'] == variable_get('shorten_service', 'is.gd')) {
//     variable_set('shorten_service', $v['name']);
//   }

  // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// if ($v['old_name'] == variable_get('shorten_service_backup', 'TinyURL')) {
//     variable_set('shorten_service', $v['name']);
//   }

  drupal_set_message(t('The changes to service %service have been saved.', array('%service' => $record['name'])));
  $_GET['destination'] = 'admin/config/services/shorten/custom';
}
