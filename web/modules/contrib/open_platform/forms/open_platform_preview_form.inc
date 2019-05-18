<?php

/**
 * @file
 * Offers funtionality for creating nodes based on results of search query.
 */

/**
 * Implements hook_form()
 * 
 * @return array
 */
function open_platform_preview_form() {
  $form = array();
  $options = array();
  $header = array();
  $empty_text = 'This data is unavailable';
  $sections = variable_get('open_platform_sections');
  $search_term = variable_get('open_platform_search');
  $fields = variable_get('open_platform_fields');
  $fields['thumbnail'] = 'thumbnail';
  $fields['trailText'] = 'trailText';
  $advanced = variable_get('open_platform_advance_fields');
  $api_key = variable_get('open_platform_api_key');
  $user_options = open_platform_options($sections, $search_term, $fields, $advanced, $api_key);
  $op_data = open_platform_get_data($user_options);
  $data = open_platform_format_data('preview', $op_data);

  // These fields are used for display purposes in the preview form.
  if (!isset($fields['thumbnail'])) {
    $fields['thumbnail'] = 'thumbnail';
  }
  if (!isset($fields['trailText'])) {
    $fields['trailText'] = 'trailText';
  }
  $x = 0;

  foreach ($data as $data_types => $values) {

    $content_id = $data['content_array'][$x]['id'];
    $headline = ($data['headline'][$x] != NULL) ? $data['headline'][$x] : $data['content_array'][$x]['webTitle'];
    $body = ($data['body'][$x] != NULL) ? $data['body'][$x] : $empty_text;
    $trailtext = ($data['trail_text'][$x] != NULL) ? $data['trail_text'][$x] : $empty_text;
    $thumbnail = ($data['thumbnail'][$x] != NULL) ? $data['thumbnail'][$x] : $empty_text;
    $wordcount = ($data['wordcount'][$x] != NULL) ? $data['wordcount'][$x] : $empty_text;
    $url = $data['content_array'][$x]['webUrl'];
    $logo = $data['logo'];

    // Check if content already exisit on site.
    $publish_check = open_platform_check_published_node($content_id);

    // Set the status messsage.
    $status = open_platform_check_status_of_content($publish_check, $body);

    $options[$content_id] = array(
      'headline' => $headline,
      'trailText' => $trailtext,
      'thumbnail' => "<img src='" . $thumbnail . "' />",
      'wordcount' => $wordcount,
      'status' => $status,
      'body' => array('#type' => 'hidden', '#value' => $body),
      'url' => array('#type' => 'hidden', '#value' => $url),
      'logo' => array('#type' => 'hidden', '#value' => $logo, 'html' => TRUE),
    );

    $x++;
  }

  // Check user chosen options.
  foreach ($fields as $field_key => $field) {
    if ($field == '0') {
      // Remove fields that have not been set from array.
      unset($fields[$field_key]);
    }

    if ($field_key == 'headline') {
      $header[$field_key] = t('Title');
    }
    elseif ($field_key == 'trailText') {
      $header[$field_key] = t('Trail Text');
    }
    elseif ($field_key == 'thumbnail') {
      $header[$field_key] = t('Thumbnail');
    }
    elseif ($field_key == 'wordCount') {
      $header[$field_key] = t('Word Count');
    }
  }

  $header['status'] = t('Status');

  // Build the 'Update options' form.
  $form['options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Update options'),
    '#attributes' => array('class' => array('container-inline')),
  );

  $form['options']['operation'] = array(
    '#type' => 'select',
    '#title' => t('Operation'),
    '#title_display' => 'invisible',
    '#options' => array('publish' => 'publish'),
    '#default_value' => 'approve',
  );
  $form['options']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#submit' => array('open_platform_preview_submit'),
  );


  $form['open_platform_preview'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No content available.'),
  );


  // Display error message if user does not have an API Key
  if (empty($api_key))  {
    drupal_set_message(t('You need an API key to publish content on your site you can obtain an API key here ') .
        l(t('http://guardian.mashery.com/'), 'http://guardian.mashery.com/' . t('once you have an API key visit the') . l(t('config page'), 'admin/config/services/open-platform')
                . t('for this module and add your API key')), 'warning');
  }
  else {
    return $form;
  }
}

/**
 * Implementats of hook_submit()
 * 
 * @return array
 */
function open_platform_preview_submit($form, &$form_state) {
  $content = $form_state['complete form']['open_platform_preview']['#options'];
  $options = $form_state['values']['open_platform_preview'];
  $fields = variable_get('open_platform_fields');
  $advanced_fields = variable_get('open_platform_advance_fields');

  // Remove non selected items.
  foreach ($options as $choice_item => $choice_value) {
    // Only act on user chosen options
    if ($choice_item === $choice_value) {
      $wordcount = (open_platform_user_selected_fields('wordcount', $advanced_fields)) ? $content[$choice_item]['wordcount'] : NULL;
      $body = ($content[$choice_item]['body']) ? $content[$choice_item]['body']['#value'] : NULL;
      $content_id = $choice_value;
      $url = $content[$choice_item]['url']['#value'];
      $headline = $content[$choice_item]['headline'];
      $logo = $content[$choice_item]['logo']['#value'];

      if ($form_state['values']['operation'] == 'publish') {
        open_platform_create_node($headline, $body, $wordcount, $content_id, $url, $logo);
      }
    }
  }
}

