<?php
/**
 * @file
 * Excluded Module Administrative screen.
 */

/**
 * Excluded settings form
 */
function excluded_settings() {
  $form = array();
  
  $form['excluded_list'] = array(
    '#type' => 'container',
    '#attributes' => array(
        'class' => array(
          'excluded-list',
        ),
      ), 
    );
  $form['excluded_list']['javascript'] = array(
    '#type' => 'fieldset',
    '#title' => t('JavaScript'),
    '#collapsible' => TRUE,
  );
  $form['excluded_list']['javascript']['excluded_javascript_default'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Main'),
    '#options' => _excluded_get_scripts(),
    '#default_value' => variable_get('excluded_javascript_default', ''),
  );
  $form['excluded_list']['javascript']['excluded_javascript_custom']  = array(
    '#type' => 'textarea',
    '#title' => t('Other'),
    '#description' => t('Enter the full path of the file you would like to exclude'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#default_value' => variable_get('excluded_javascript_custom', ''),
  );
    $form['excluded_list']['styles'] = array(
    '#type' => 'fieldset',
    '#title' => t('CSS'),
    '#collapsible' => TRUE,
  );
  
  $form['excluded_list']['styles']['excluded_css_default'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Main'),
    '#options' => _excluded_get_styles(),
    '#default_value' => variable_get('excluded_css_default', ''),
  );
  $form['excluded_list']['styles']['excluded_css_custom']  = array(
    '#type' => 'textarea',
    '#title' => t('Other'),
    '#description' => t('Enter the full path of the file you would like to exclude'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#default_value' => variable_get('excluded_css_custom', ''),
  );

  return system_settings_form($form);
}

function _excluded_get_scripts() {
  $output = array();
  $js = drupal_add_js();
  
  foreach($js as $script => $file) {
    if( is_string($file['data']) ) {
      $output[$script] = $file['data'];
    }
  }

  return $output;
}

function _excluded_get_styles() {
  $output = array();
  $css = drupal_add_css();

  foreach($css as $styles => $file) {
    if( is_string($file['data']) ) {
      $output[$styles] = $file['data'];
    }
  }

  return $output;
}