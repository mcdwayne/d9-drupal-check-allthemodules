<?php

/**
 * @file
 * Administration functions for the IP Ban module.
 */

/**
 * Ip_ban admin form.
 */
function ip_ban_admin($form, &$form_state) {
  $form = array();
  $form['#attached']['js'] = array(
    drupal_get_path('module', 'ip_ban') . '/ip_ban.js',
  );
  // Add a second submit button.
  $form['top_submit_button'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );
  // @FIXME
  // Could not extract the default value because it is either indeterminate, or
  // not scalar. You'll need to provide a default value in
  // config/install/ip_ban.settings.yml and config/schema/ip_ban.schema.yml.
  $form['ip_ban_readonly'] = array(
      '#type' => 'textfield',
      '#title' => t('Read Only Message'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_readonly'),
      '#description' => t('The message that a user from a country set to "Read Only" will see when they attempt to access any /user/* page on this website. This message will be shown and highlighted as an error.'),
      '#size' => 100,
      '#maxlength' => 256,
    );
  $form['ip_ban_readonly_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Page to redirect to if user attempts to access any user/* page based on read-only access'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_path'),
    '#description' => t('Enter a valid internal path, such as "node/1" or "content/read-only". If no path is provided, the user will be redirected to the home page.'),
    '#size' => 100,
    '#maxlength' => 256,
  );
  $form['ip_ban_completeban'] = array(
    '#type' => 'textfield',
    '#title' => t('Complete Ban Message'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_completeban'),
    '#description' => t('The message that a user from a country set to "Complete Ban" will see when they try to access any page on this website (except the access denied or redirect page. This message will be shown and highlighted as an error.'),
    '#size' => 100,
    '#maxlength' => 256,
  );
  $form['ip_ban_completeban_path'] = array(
    '#type' => 'textfield',
    '#title' => t('Page to redirect to if user attempts to access any webpage based on "Complete Ban" access'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_completeban_path'),
    '#description' => t('Enter a valid internal path, such as "node/1" or "content/banned". If no path is provided, the user will be redirected to access denied page (if set). If no path is set here, and no path is set for access denied, the user will only see an error message on every page.'),
    '#size' => 100,
    '#maxlength' => 256,
  );
  $options = array(
    IP_BAN_NOBAN => '',
    IP_BAN_READONLY => t('Read Only'),
    IP_BAN_BANNED => t('Complete Ban'),
  );
  // @FIXME
  // Could not extract the default value because it is either indeterminate, or
  // not scalar. You'll need to provide a default value in
  // config/install/ip_ban.settings.yml and config/schema/ip_ban.schema.yml.
  $form['ip_ban_setdefault'] = array(
    '#type' => 'select',
    '#title' => t('Dynamically set the default value for each country.'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_setdefault'),
    '#description' => t('Apply this setting once before you override individual countries below. Applying this setting will take a few moments to complete.'),
    '#options' => $options,
  );

  // This is a dummy element used to iterate through its children when
  // the country options table is themed.
  $form['ip_ban_table'] = array(
    // '#theme' => 'ip_ban_country_table',
    '#type' => 'table',
    '#title' => t('Country Listing Table'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  // Add each country selector as a child of the dummy element.
  // @see https://www.drupal.org/node/2019329
  $countries = Drupal::service('country_manager')->getList();
  foreach ($countries as $country_code => $country_name) {
    $form_name = 'ip_ban_' . $country_code;
    // @FIXME
    // The correct configuration object could not be determined. You'll need to
    // rewrite this call manually.
    $form['ip_ban_table'][$form_name] = array(
          '#type' => 'select',
          '#title' => t($country_name),
          '#options' => $options,
          '#default_value' => \Drupal::config('ip_ban.settings')->get(IP_BAN_NOBAN),
          '#attributes' => array('class' => array('ip-ban-table-cell')),
        );
  }
  $form['ip_ban_additional_ips'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter additional individual IP addresses to ban'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_additional_ips'),
    '#description' => t('Add one IPV4 address per line. Example:<br/>127.0.0.1<br/>156.228.60.110'),
  );
  $form['ip_ban_readonly_ips'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter additional individual IP addresses to allow read-only access'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_ips'),
    '#description' => t('Add one IPV4 address per line. Example:<br/>127.0.0.1<br/>156.228.60.110'),
  );
  $form['ip_ban_disabled_blocks'] = array(
    '#type' => 'textarea',
    '#title' => t('Enter blocks to disable for users in "read only" mode'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_disabled_blocks'),
    '#description' => t('<p>Add one module name (that implements the block) and delta per line, separated by a comma. If you are unsure of the module name or delta, navigate to the block configuration page. The module name will be the third to last part of the URI, and the delta will be the second to last. For example, for /admin/structure/block/manage/user/login/configure, enter "user,login" without the quotes. For a custom block like /admin/structure/block/manage/block/11/configure, enter "block,11" without the quotes.</p><p><strong>Note</strong>: there is no validation to determine if the blocks entered are enabled for any enabled theme or the admin theme.</p>'),
    '#element_validate' => array('ip_ban_disabled_blocks_validate'),
  );
  $form['ip_ban_test_ip'] = array(
    '#type' => 'textfield',
    '#title' => t('Test IP address'),
    '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_test_ip'),
    '#description' => t('Enter one valid IPV4 address to test your settings. Example: 156.228.60.110'),
  );
  $form = system_settings_form($form);
  $form['#validate'][] = 'ip_ban_validate';
  return $form;
}

/**
 * Custom form validation function.
 *
 * Custom form validation function simply calls internal function to reduce
 * code duplication since we're checking for the same issue in two different
 * instances.
 */
function ip_ban_validate($form, &$form_state) {
  _ip_ban_validate_paths('ip_ban_readonly_path', \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_readonly_path']));
  _ip_ban_validate_paths('ip_ban_completeban_path', \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_completeban_path']));
  _ip_ban_validate_ips('ip_ban_additional_ips', \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_additional_ips']));
  _ip_ban_validate_ips('ip_ban_readonly_ips', \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_readonly_ips']));
  _ip_ban_validate_ips('ip_ban_test_ip', \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_test_ip']));
}

/**
 * Custom validation function for path redirects.
 *
 * Custom validation function for the path to redirect to for banned or
 * read-only users. Here we simply ensure the path specified exists, and if not,
 * display a form error.
 */
function _ip_ban_validate_paths($form_element, $form_value) {
  // An empty path is valid here because the path is not a required field.
  if (!empty($form_value) && htmlspecialchars_decode($form_value) != '<front>') {
    $normal_path = drupal_get_normal_path($form_value);
    if (!\Drupal::service("path.validator")->isValid($normal_path)) {
      form_set_error($form_element, t('The path entered does not exist or you do not have permission to access it.'));
    }
  }
}

/**
 * Custom validation function for valid IP addresses.
 *
 * Custom validation function for the list of additional IP addresses to either
 * ban or mark as read-only. We convert the textarea into an array of IP
 * addresses, then check if each address is valid. If any one line is invalid,
 * we set the entire form element to invalid.
 */
function _ip_ban_validate_ips($form_element, $form_value) {
  if (!empty($form_value)) {
    $ip_array = explode(PHP_EOL, $form_value);
    foreach ($ip_array as $ip) {
      if (filter_var(trim($ip), FILTER_VALIDATE_IP) == FALSE) {
        form_set_error($form_element, t('You have entered one or more incorrect IPV4 addresses.'));
      }
    }
  }
}

/**
 * Determine if blocks entered are valid and formatted correctly.
 */
function ip_ban_disabled_blocks_validate($form, &$form_state) {
  $disabled_blocks = \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_disabled_blocks']);
  if (!empty($disabled_blocks)) {
    $disabled_block_array = explode(PHP_EOL, $disabled_blocks);
    foreach ($disabled_block_array as $disabled_block) {
      // First determine if the user entered two strings separated by a space.
      $module_and_delta = explode(',', trim($disabled_block));
      if (count($module_and_delta) != 2) {
        form_set_error('ip_ban_disabled_blocks', t('You have one or more blocks with an incorrect format; you must enter exactly one module name and delta name per line, separated by a comma.'));
      }
      else {
        $module = trim($module_and_delta[0]);
        $delta = trim($module_and_delta[1]);
        // Second determine if the block entered is a valid block.
        $disabled_block = db_query('SELECT * FROM {block} WHERE module = :module AND delta = :delta', array(':module' => $module, ':delta' => $delta))->fetchAll();
        if (empty($disabled_block)) {
          form_set_error('ip_ban_disabled_blocks', t('You entered at least one invalid module name or delta; see the help text for how to enter the proper module name and delta.'));
        }
        // Todo: add check for block enabled status for enabled themes. If block
        // disabled for all enabled themes (including admin theme), set form
        // error.
      }
    }
  }
}

/**
 * Theme the IP Ban Country configuration table.
 */
function theme_ip_ban_country_table($form) {
  $countries = \Drupal\Core\Render\Element::children($form['ip_ban_table']);
  $header = array(t('Country'), t('Policy'));
  foreach ($countries as $country) {
    // Put the select element's title in its own cell.
    $country_name = $form['ip_ban_table'][$country]['#title'];
    unset($form['ip_ban_table'][$country]['#title']);
    $rows[] = array(
      $country_name,
      \Drupal::service("renderer")->render($form['ip_ban_table'][$country]),
    );
  }
  // @see https://www.drupal.org/node/2195739
  $output  = array(
    '#type' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#attributes' => array(
      'id' => 'ip-ban-country-listing-table',
      'style' => 'width: 50%',
    )
  );

  return \Drupal::service('renderer')->render($output);
}
