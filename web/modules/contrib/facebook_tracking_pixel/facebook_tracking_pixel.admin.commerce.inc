<?php

/**
 * @file facebook_tracking_pixel.admin.commerce.inc
 * Commerce tracking capabilities admin forms and functions.
 *
 * @author Brady Owens <info@fastglass.net>
 */

/**
 * Form builder for commerce tracking.
 *
 * @param $form
 * @param $form_state
 * @return mixed
 */
function facebook_tracking_pixel_commerce_form($form, &$form_state) {

  if (!module_exists('commerce')) {
    $form['intro0'] = [
      '#markup' => t('This portion of the module is only compatible with Drupal Commerce.'),
    ];
    $form['intro1'] = [
      '#markup' => l('Download', 'https://www.drupal.org/project/commerce', ['external' => TRUE]) . ' ' . t('Drupal Commerce and install it to use these functions.'),
    ];
    return $form;
  }
  $currencycodesraw = commerce_currencies();
  $currencycodes = [];
  foreach ($currencycodesraw as $key => $value) {
    $currencycodes[$key] = $value['name'] . ' (' . $key . ')';
  }
  $form['intro0'] = [
    '#markup' => t('Enabling this will track user registration for the entire site. To not track users added by admins, do not enable tracking for their roles.'),
  ];
  //Check to see if there are base codes.
  $resultcountbasecode = db_select('facebook_tracking_pixel_base_codes', 'c')
    ->fields('c')
    ->countQuery()
    ->execute()
    ->fetchField();
  if ($resultcountbasecode == 0) {
    $form['emptyset'] = [
      '#type' => 'hidden',
      '#value' => TRUE,
    ];
    $form['notice'] = [
      '#markup' => t('You must first set a base code for tracking.'),
    ];
    return $form;
  }
  else {
    // If we have results then build an array to use as an option drop down for
    // the path items.
    $resultbasecode = db_select('facebook_tracking_pixel_base_codes', 'c')
      ->fields('c')
      ->orderBy('weight')
      ->execute()
      ->fetchAllAssoc('base_code_id');
    $basecodes = [];
    foreach ($resultbasecode as $item) {
      $basecodes[$item->base_code_id] = $item->base_code_name;
    }
  }
  $enable = 0;
  if (variable_get('facebook_tracking_pixel_commerce_tracking_enable')) {
    $enable = 1;
  }
  if (!empty($form_state['values'])) {
    if (variable_get('facebook_tracking_pixel_commerce_tracking_enable') && empty($form_state['values']['facebook_tracking_pixel_commerce_tracking_enable'])) {
      $enable = 0;
    }
  }

  if (!variable_get('facebook_tracking_pixel_commerce_tracking_enable') && !empty($form_state['values']['facebook_tracking_pixel_commerce_tracking_enable'])) {
    $enable = 1;
  }

  $form['facebook_tracking_pixel_commerce_tracking_enable'] = [
    '#title' => t('Enable the tracking of Drupal Commerce sales.'),
    '#type' => 'checkbox',
    '#default_value' => variable_get('facebook_tracking_pixel_commerce_tracking_enable'),
    '#ajax' => [
      'callback' => 'facebook_tracking_pixel_commerce_tracking_ajax',
      'wrapper' => 'replace-facebook-tracking-pixel-commerce-tracking-basecodes',
      'effect' => 'fade',
    ],
  ];
  $form['facebook_tracking_pixel_commerce_tracking_options'] = [
    '#type' => 'fieldset',
    '#title' => t('Enable Drupal Commerce tracking to set options.'),
    '#prefix' => '<div id="replace-facebook-tracking-pixel-commerce-tracking-basecodes">',
    '#suffix' => '</div>',
  ];
  if ($enable) {
    $form['facebook_tracking_pixel_commerce_tracking_options']['#title'] = t('Commerce tracking options.');
    $options = variable_get('facebook_tracking_pixel_commerce_tracking_options_selection', []);
    $form['facebook_tracking_pixel_commerce_tracking_options']['facebook_tracking_pixel_commerce_tracking_options_selection'] = [
      '#type' => 'checkboxes',
      '#title' => t('Events to track'),
      '#options' => [
        'checkoutstart' => t('Checkout Start'),
        'addtocart' => t('Add to Cart'),
        'purchase' => t('Purchase'),
      ],
      '#default_value' => $options,
    ];
    $form['facebook_tracking_pixel_commerce_tracking_options']['facebook_tracking_pixel_commerce_tracking_basecode'] = [
      '#type' => 'select',
      '#title' => t('Facebook Base Tracking Code to Use'),
      '#options' => $basecodes,
      '#default_value' => variable_get('facebook_tracking_pixel_commerce_tracking_basecode', NULL),
    ];

  }
  $form['submit'] = [
    '#type' => 'submit',
    '#value' => 'Save configuration',
  ];

  return $form;
}

/**
 * AJAX callback to populate options for tracking.
 *
 * @param $form
 * @param $form_state
 * @return mixed
 */
function facebook_tracking_pixel_commerce_tracking_ajax($form, $form_state) {
  return $form['facebook_tracking_pixel_commerce_tracking_options'];
}

/**
 * Submit handler for commerce tracking admin form.
 *
 * @param $form
 * @param $form_state
 */
function facebook_tracking_pixel_commerce_form_submit($form, $form_state) {
  // Sanitize variables for good measure.
  $enabled = check_plain($form_state['values']['facebook_tracking_pixel_commerce_tracking_enable']);
  !empty($enabled) ? variable_set('facebook_tracking_pixel_commerce_tracking_enable', $enabled) : variable_set('facebook_tracking_pixel_commerce_tracking_enable', 0);

  if (!empty($form_state['values']['facebook_tracking_pixel_commerce_tracking_basecode'])) {
    // This is saving the basecode ID number - not the basecode itself.
    $basecodeid = check_plain($form_state['values']['facebook_tracking_pixel_commerce_tracking_basecode']);
    variable_set('facebook_tracking_pixel_commerce_tracking_basecode', $basecodeid);

    $base_code_fbid = db_select('facebook_tracking_pixel_base_codes', 'c')
      ->fields('c', ['base_code_fbid'])
      ->condition('base_code_id', $basecodeid, '=')
      ->execute()
      ->fetchField();
  }
  if (!empty($form_state['values']['facebook_tracking_pixel_commerce_tracking_currency_selection'])) {
    // Storing the currency selection.
    variable_set('facebook_tracking_pixel_commerce_tracking_currency_selection', check_plain($form_state['values']['facebook_tracking_pixel_commerce_tracking_currency_selection']));
  }
  if (!empty($form_state['values']['facebook_tracking_pixel_commerce_tracking_options_selection'])) {
    $fb_script_src = facebook_tracking_pixel_base_code_js();
    $events = facebook_tracking_pixel_events_reserved();
    $subdir = variable_get('facebook_tracking_pixel_commerce_js_subdir', 'commercetracking');
    // This will create an array for the variable and only store the values that were checked.
    $selectedoptions = array_filter($form_state['values']['facebook_tracking_pixel_commerce_tracking_options_selection']);
    if (!array_key_exists('purchase', $selectedoptions)) {
      // If the Add to Cart is not checked we delete the variable for currency.
      variable_del('facebook_tracking_pixel_commerce_tracking_currency_selection');
    }

    // We santizie form input since this data will get saved to the DB.
    $clean = [];
    foreach ($selectedoptions as $key => $val) {
      $clean[check_plain($key)] = check_plain($val);
      switch ($key) {
        case 'addtocart':
          $code = $fb_script_src[0] . $base_code_fbid . $fb_script_src[1] . $events['ftpdcaddtocart']['code'];
          $filename = 'fb_trk_addtocart_' . $base_code_fbid . '.js';
          facebook_tracking_pixel_delete_file($filename, $subdir);
          facebook_tracking_pixel_save_file($code, $filename, $subdir);
          break;
        case 'checkoutstart':
          $code = $fb_script_src[0] . $base_code_fbid . $fb_script_src[1] . $events['ftpdcinitiatecheckout']['code'];
          $filename = 'fb_trk_checkoutstart_' . $base_code_fbid . '.js';
          facebook_tracking_pixel_delete_file($filename, $subdir);
          facebook_tracking_pixel_save_file($code, $filename, $subdir);
          break;
      }
    }
    variable_set('facebook_tracking_pixel_commerce_tracking_options_selection', $clean);
    drupal_set_message(t('Commerce Tracking Settings Saved.'), 'status', FALSE);
  }
  else {
    variable_set('facebook_tracking_pixel_commerce_tracking_options_selection', []);
    drupal_set_message(t('Commerce Tracking Settings Saved.'), 'status', FALSE);
  }
}
