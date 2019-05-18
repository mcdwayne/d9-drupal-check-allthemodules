<?php
/**
 * The settings for for the CloudMenu module.
 */
function cloudmenu_admin_settings() {
  $form = array();
  $form['blocks'] = array(
    '#type' => 'textfield',
    '#title' => t('Number of blocks'),
    '#default_value' => config('cloudmenu.settings')->get('blocks') ?: 1,
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('This is the number of CloudMenu blocks you will be able to configure on !link', array('!link.' => l('your blocks configuration page', 'admin/structure/block'))),
  );
  $form['#validate'][] = '_cloudmenu_settings_validate';

  return system_settings_form($form);
}

/**
 * Validation handler for the admin settings form.
 */
function _cloudmenu_settings_validate($form, &$form_state) {
  $blocks = $form_state['values']['blocks'];
  if ($blocks < 0) {
    form_set_error('blocks', t("The number of blocks can not be a negative number"));
  }
}

/**
 * Submit handler for the admin settings form.
 */
function cloudmenu_admin_settings_submit($form, &$form_state) {
  config('cloudmenu.settings')->set('blocks', $form_state['values']['blocks'])->save();

  // Check that default settings exist for each defined block.
  for ($delta = 0; $delta < $form_state['values']['blocks']; $delta++) {
    $settings = config('cloudmenu.block.' . $delta);
    if ($settings->isNew()) {
      $defaults = cloudmenu_defaults($delta);
      foreach ($defaults as $key => $value) {
        $settings('cloudmenu.block.' . $delta)->set($key, $value);
      }
      $settings->save();
    }
  }
}
