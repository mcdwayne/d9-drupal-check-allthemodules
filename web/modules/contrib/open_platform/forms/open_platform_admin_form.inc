<?php

/**
 * @file
 * Admin settings for The Open Platfrom module.
 */

/**
 * Implements hook_form().
 * 
 * @return array
 */
function open_platform_settings_form() {
  $form = array();

  $field_options = array(
    'trailText' => 'Trail text',
    'thumbnail' => 'Thumbnail',
  );

  $form['open_platform_sections'] = array(
    '#type' => 'select',
    '#title' => t('Sections'),
    '#default_value' => variable_get('open_platform_sections'),
    '#options' => array(
      open_platform_get_end_points('sections'),
    ),
  );

  $form['open_platform_search'] = array(
    '#type' => 'textfield',
    '#title' => t('Search'),
    '#default_value' => variable_get('open_platform_search'),
    '#description' => t('Enter enter keyword(s)'),
    '#size' => 60,
    '#maxlength' => 128,
  );

  $form['open_platform_fields'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Block fields'),
    '#default_value' => variable_get('open_platform_fields'),
    '#description' => t('Select what fields you would like to display in your blocks'),
    '#options' => $field_options,
  );

  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#description' => t('Add your API key and select extra options here'),
  );

  $form['advanced']['open_platform_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('API Key'),
    '#default_value' => variable_get('open_platform_api_key'),
    '#description' => 'In order to recieve full content you will need to add your API key',
  );

  $form['advanced']['open_platform_advance_fields'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Content Fields'),
    '#default_value' => variable_get('open_platform_advance_fields'),
    '#description' => t('If you have a valid API key you can display the full body of an article'),
    '#options' => array(
      'body' => t('Body field'),
      'wordcount' => t('Word count'),
    ),
  );

  $form['advanced']['open_platform_block_cache'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Enable block cache'),
    '#default_value' => variable_get('open_platform_block_cache'),
    '#description' => t('The number of queries you can make with an API key is 
        limited to 5K and less without an API KEY. Displaying the block that 
        this module provides means that a web service call is made to the 
        Guardian Open Platform on every page request. If your site is very busy
        you may want to consider turning this option on. This will also help to
        improve performance on pages displaying the Open Platform block.'),
    '#options' => array(
      'cache' => t('Turn on block content cache'),
    ),
  );

  return system_settings_form($form);
}
