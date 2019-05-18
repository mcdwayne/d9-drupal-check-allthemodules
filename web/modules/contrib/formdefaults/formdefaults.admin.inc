<?php
// $Id$
/**
 * @file formdefaults.admin.inc
 * Include for formdefaults administration screen.
 */

use Drupal\Core\Url;
use Drupal\formdefaults\Helper\FormDefaultsHelper;

/* :TODO: move to D8 form object
function formdefaults_export() {
 $search_str = @$_SESSION['formdefaults_search'];

  $form['search_str'] = array(
    '#type' => 'textfield',
    '#default_value' => $search_str,
  );

  $form['search'] = array(
      '#type' => 'submit',
      '#value' => 'Search',
      '#size' => 10,
  );

  // :TODO: convert to $helper->search($search_str);
  $form_list = _formdefaults_search($search_str);

  $form['count'] = array(
    '#type' => 'item',
    '#title' => t('Forms Exported'),
    '#value' => count((array)$form_list),
  );

  $form_data = base64_encode(serialize($form_list));
  $form['data'] = array(
      '#type' => 'textarea',
      '#title' => 'Data',
      '#default_value' => $form_data,
      '#rows' => 20,
      '#cols' => 80,
  );
  return $form;
}

function formdefaults_export_submit($formid, &$form_state) {
  $form_values = $form_state->getValues();
  $_SESSION['formdefaults_search'] = $form_values['search_str'];
}
*/

/* :TODO: move to FormDefaultsHelper
function formdefaults_data($data='') {
  static $d = '';
  if (!$d) $d = @$_SESSION['formdefaults_data'];

 // if (!$d) $d=array();
  if ($data) {
    $d = $data;
  }
  $_SESSION['formdefaults_data'] = $d;
  return $d;
}
 */

/* :TODO: move to D8 form object
function formdefaults_import() {
  $form_list = formdefaults_data();
  $data= '';
  if ($form_list) $data = base64_encode(serialize($form_list));

  $form['data'] = array(
        '#type' => 'textarea',
        '#title' => 'Data',
        '#default_value' => $data,
        '#rows' => 20,
        '#cols' => 80,
  );
  $form['list'] = array('#type' => 'fieldset',
                        '#title' => 'Forms');


  if ($form_list) {
    foreach ($form_list as $key => $formdata) {
      $form['list'][] = array('#type' => 'markup',
                             '#markup' => "<p>$key</p>",
                             );
    }
  }

  $form['validate'] = array(
        '#type' => 'submit',
        '#value' => 'Preview',
  );
  if ($data) {
    $form['import'] = array(
          '#type' => 'submit',
          '#value' => 'Import',
    );
  }
  return $form;
}

function formdefaults_import_submit($formid, &$form_state) {

  $form_values = $form_state['values'];
  $forms = unserialize(base64_decode($form_values['data']));
  formdefaults_data($forms);
  if ($_POST['op']=='Import') {
    foreach ($forms as $key => $formdata) {
      $helper = new FormDefaultsHelper();
      $helper->saveForm($key, $formdata);
    }
    unset($_SESSION['formdefaults_data']);
  }

}
 */

function formdefaults_filter_element($fmt) {

  global $user;
  $element['format'] =  array(
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#title' => t('Input formats'),
  );
    // Get a list of formats that the current user has access to.
  $formats = filter_formats($user);
  foreach ($formats as $format) {
    $options[$format->format] = $format->name;
    $element['format']['guidelines'][$format->format] = array(
      '#theme' => 'filter_guidelines',
      '#format' => $format,
    );
  }
  $element['format']['guidelines']['#weight']=12;

  $element['format']['input_format'] = array(
    '#type' => 'select',
    '#title' => t('Text format'),
    '#options' => $options,
    '#default_value' => $fmt,
    '#access' => count($formats) > 1,
    '#weight' => 10,
    '#attributes' => array('class' => array('filter-list')),
  );

  $element['format']['help'] = array(
    '#type' => 'container',
    '#theme' => 'filter_tips_more_info',
    '#attributes' => array('class' => array('filter-help')),
    '#weight' => 11,
  );
  return $element['format'];
}
