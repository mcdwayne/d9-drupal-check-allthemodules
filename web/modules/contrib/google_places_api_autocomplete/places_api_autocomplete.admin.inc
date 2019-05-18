<?php

/**
 * @file
 * Admin page callback file for the places_api_autocomplete module.
 */

/**
 * Form builder; Configure places_api_autocomplete settings for this site.
 *
 * @ingroup forms
 *
 * @see system_settings_form()
 */
function places_api_autocomplete_admin_settings() {
  $form['places_api_autocomplete_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Google API key'),
    '#description' => t('You can get it from your <a href="https://code.google.com/apis/console">Google Console</a>.'),
    '#default_value' => variable_get('places_api_autocomplete_key', ''),
  );

  $documentation_link = variable_get('places_api_autocomplete_documentation_link');
  $options = variable_get('places_api_autocomplete_options', places_api_autocomplete_get_default_options());

  $form['places_api_autocomplete_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Places API parameters'),
    '#tree' => TRUE,
    '#description' => t('The values for the parameters will be used when the autocomplete path is used outside of the scope of a field widget (I.E.: FAPI autocomplete path). If you are using the field widget, these settings will have no effect. Documentation about the parameters, can be found <a target="_blank" href="@url">here</a>.', array('@url' => $documentation_link)),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['places_api_autocomplete_options']['offset'] = array(
    '#type' => 'textfield',
    '#title' => t('Offset'),
    '#default_value' => $options['offset'],
  );
  $form['places_api_autocomplete_options']['location'] = array(
    '#type' => 'textfield',
    '#title' => t('Location'),
    '#default_value' => $options['location'],
  );
  $form['places_api_autocomplete_options']['radius'] = array(
    '#type' => 'textfield',
    '#title' => t('Radius'),
    '#default_value' => $options['radius'],
  );
  $form['places_api_autocomplete_options']['language'] = array(
    '#type' => 'textfield',
    '#title' => t('Language'),
    '#default_value' => $options['language'],
  );
  $form['places_api_autocomplete_options']['types'] = array(
    '#type' => 'textfield',
    '#title' => t('Types'),
    '#default_value' => $options['types'],
  );
  $form['places_api_autocomplete_options']['components'] = array(
    '#type' => 'textfield',
    '#title' => t('Components'),
    '#default_value' => $options['components'],
  );
  $form['places_api_autocomplete_options']['minlength'] = array(
    '#type' => 'textfield',
    '#title' => t('Minimum Query Length'),
    '#default_value' => isset($settings['places_api_autocomplete_options']['minlength']) ? $settings['places_api_autocomplete_options']['minlength'] : $options['minlength'],
  );

  return system_settings_form($form);
}
