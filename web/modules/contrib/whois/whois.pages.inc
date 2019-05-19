<?php

/**
 * @file
 * Contains the form and page functions
 */


function whois_whois_page() {
  if (!\Drupal::currentUser()->hasPermission('access whois')) {
    return;
  }

  $output = '';
  $address = !empty($_POST['whois_address']) ? $_POST['whois_address'] : arg(1);

  if (isset($address)) {
    // Check for hourly threshold.
    if (flood_is_allowed('whois', \Drupal::config('whois.settings')->get('whois_hourly_threshold'))) {
      $output .= whois_display_whois($address);
    }
    else {
      $output .= t("You cannot do more than %number whois lookups per hour. Please try again later.", array('%number' => \Drupal::config('whois.settings')->get('whois_hourly_threshold')));
    }
    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// drupal_set_breadcrumb(array(l(t('Home'), '<front>'), l(t('Whois lookup'), 'whois')));

  }
  // Load JS and CSS for dynamic lookups using AJAX.
  if (\Drupal::config('whois.settings')->get('whois_enable_ajax')) {
    // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
// 
// 
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_css(drupal_get_path('module', 'whois') . '/whois.css');

  }

  $output = '<div id="live-whois-preview" class="whois-preview">' . $output . '</div>';
  $build['whois_form'] = \Drupal::formBuilder()->getForm('whois_whois_form');
  $build['whois_lookup_data'] = array('#markup' => $output);

  return $build;
}

/**
 * Implements hook_form().
 */
function whois_whois_form() {
  $form = array();

  $form['whois_lookup'] = array(
    '#type' => 'fieldset',
    '#collapsed' => TRUE,
  );
  $form['whois_lookup']['whois_address'] = array(
    '#type' => 'textfield',
    '#title' => t('Lookup address'),
    '#default_value' => arg(1),
    '#required' => TRUE,
    '#prefix' => '<div class="container-inline">',
  );
  $form['whois_lookup']['whois_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Lookup'),
    '#suffix' => '</div>',
  );
  $form['whois_lookup']['whois_description'] = array(
    '#markup' => '<div class="description" style="margin: 0;">' . t('Enter a domain name or IP address for <em>whois</em> information.') . '</div>',
  );

  if (\Drupal::config('whois.settings')->get('whois_enable_ajax')) {
    $form['whois_lookup']['whois_submit']['#ajax'] = array(
      'callback' => 'whois_ajax',
      'wrapper' => 'live-whois-preview',
      'event' => 'click'
    );
  }

  return $form;
}


function whois_whois_form_submit($form, &$form_state) {
  $user = \Drupal::currentUser();

  $result = _whois_cleanup_address($form_state['values']['whois_address']);
  $address = $result['prepared_address'];

  $form_state['redirect'] = check_url('whois/' . $address);
  return;
}
