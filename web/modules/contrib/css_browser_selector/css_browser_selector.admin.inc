<?php

/**
 * The CSS Browser Selector admin config form.
 */
function css_browser_selector_admin_settings($form, &$form_state) {
  $config = config('css_browser_selector.config');
  $use_js = $config->get('use_js');

  $form = array();

  $form['settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Settings'),
  );
  $form['settings']['use_js'] = array(
    '#type' => 'radios',
    '#title' => t('Use Javascript'),
    '#description' => t('When enabled, the CSS selectors will be
      injected into the HTML tag after the page loads.
      <strong>You must use this feature if you intend to cache
      your pages.</strong>'),
    '#options' => array(
      0 => 'No',
      1 => 'Yes',
    ),
    '#default_value' => empty($use_js) ? 0 : $use_js,
  );
  $form['settings']['class_variables'] = array(
    '#type' => 'textarea',
    '#title' => t('Class variables'),
    '#description' => t('These are the names of the variables that
      will contain the browser selectors.  You may print any of these
      variables in any class tag in your tpl.php files.  Enter one
      name per line.'),
    '#default_value' => $config->get('class_variables'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Submit handler for the CSS Browser Selector admin config form.
 */
function css_browser_selector_admin_settings_submit($form, &$form_state) {
  config('css_browser_selector.config')
    ->set('use_js', $form_state['values']['use_js'])
    ->set('class_variables', $form_state['values']['class_variables'])
    ->save();
}
