<?php

/**
 * @file
 * Administrative pages for the Word Link module.
 */

/**
 * Form builder for a settings page.
 */
function word_link_admin_settings() {
  $form['#tree'] = TRUE;
  $form['word_link_settings'] = array(
    '#type' => 'details',
    '#title' => t('Settings'),
  );
  $form['word_link_settings']['word_link_node_types'] = array(
    '#type' => 'details',
    '#title' => t('Node types'),
    '#description' => t('Choose the fields into which Word Link will automatically insert links.'),
    '#collapsed' => FALSE,
  );
  $node_types = node_type_get_names();
  $type_defaults = variable_get('word_link_node_types', array());
  foreach ($type_defaults as $content => $type) {
    $types = array();
    foreach ($type['fields'] as $name => $field) {
      if (!is_array($field)) {
        $types[$field] = $field;
      }
      else {
        foreach ($field as $fc_field) {
          $types[$name . '__' . $fc_field] = $name . '__' . $fc_field;
        }
      }
    }
    $type_defaults[$content]['fields'] = isset($types) ? $types : array();
  }
  foreach ($node_types as $type => $name) {
    $fields = word_link_get_node_fields($type);
    if (!empty($fields)) {
      if (module_exists('comment')) {
        $fields['comments'] = t('Comments');
      }
      $default_values = isset($type_defaults[$type]['fields']) ? $type_defaults[$type]['fields'] : array();
      $default_values = is_array($default_values) ? array_filter($default_values) : array();
      $form['word_link_settings']['word_link_node_types'][$type] = array(
        '#type' => 'details',
        '#title' => $name,
        '#collapsed' => empty($default_values),
      );
      $form['word_link_settings']['word_link_node_types'][$type]['fields'] = array(
        '#type' => 'select',
        '#options' => $fields,
        '#multiple' => TRUE,
        '#tree' => TRUE,
        '#default_value' => $default_values,
      );
    }
  }
  $form['word_link_settings']['word_link_css'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add default CSS'),
    '#description' => t('If turned on default CSS from module will be added.'),
    '#default_value' => variable_get('word_link_css', 1),
  );
  $form['word_link_settings']['word_link_limit'] = array(
    '#type' => 'textfield',
    '#size' => 5,
    '#maxlenghth' => 3,
    '#title' => t('Convert limit'),
    '#description' => t('Set the maxium instances that will be converted. Use 0 if you want all instances in the node to be converted.'),
    '#default_value' => variable_get('word_link_limit', 0),
  );
  $form['word_link_settings']['word_link_tags_except'] = array(
    '#type' => 'textfield',
    '#title' => t('Disallowed HTML tags'),
    '#description' => t('A list of HTML tags that will be ignored. Never enter here tags that are not text. E.g. @tags.', array('@tags' => '<img>')),
    '#default_value' => variable_get('word_link_tags_except', '<h1> <h2> <h3> <h4> <h5> <h6>'),
  );
  $form['word_link_add_form']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Form validation for a settings page.
 */
function word_link_admin_settings_validate($form, &$form_state) {
  $limit = $form_state['values']['word_link_settings']['word_link_limit'];
  if (!is_numeric($limit) || (int) $limit < 0) {
    form_set_error('word_link_settings][word_link_limit', t('Replace limit must be numeric.'));
  }
}

/**
 * Submit form for a settings page.
 */
function word_link_admin_settings_submit($form, &$form_state) {
  $types = $form_state['values']['word_link_settings']['word_link_node_types'];
  foreach ($form_state['values']['word_link_settings']['word_link_node_types'] as $content => $type) {
    $fields = array();
    foreach ($type['fields'] as $field) {
      if (preg_match('/.+__.+/', $field) != 0) {
        $fc = explode('__', $field);
        $fields[$fc[0]][$fc[1]] = $fc[1];
      }
      else {
        $fields[$field] = $field;
      }
    }
    $types[$content]['fields'] = $fields;
  }
  variable_set('word_link_limit', $form_state['values']['word_link_settings']['word_link_limit']);
  variable_set('word_link_tags_except', $form_state['values']['word_link_settings']['word_link_tags_except']);
  variable_set('word_link_node_types', $types);
  variable_set('word_link_css', $form_state['values']['word_link_settings']['word_link_css']);
  drupal_set_message(t('Saved'));
}

/**
 * Buid a Word Link list page.
 */
function word_link_list_page_form($form, $form_state) {
  // Return confirm page when need to delete words.
  if (!empty($form_state['values']['words'])) {
    return word_link_list_page_form_delete_confirm($form, $form_state, array_filter($form_state['values']['words']));
  }
  // Build the sortable table header.
  $header = array(
    'id' => array('data' => t('id'), 'field' => 'wl.id', 'sort' => 'desc'),
    'text' => array('data' => t('Text'), 'field' => 'wl.text'),
    'case_sensitive' => array('data' => t('Case sensitive'), 'field' => 'wl.case_sensitive'),
    'url' => array('data' => t('URL'), 'field' => 'wl.url'),
    'url_title' => array('data' => t('URL title'), 'field' => 'wl.url_title'),
    'class' => array('data' => t('Class'), 'field' => 'wl.class'),
    'except' => array('data' => t('Visibility'), 'field' => 'wl.except'),
    'operations' => array('data' => t('Operations')),
  );
  // Get words from DB.
  $words = word_link_get_link(NULL, $header, 50);

  $destination = drupal_get_destination();
  $options = array();
  foreach ($words as $delta => $word) {
    $visibility = '';
    if (!empty($word->except)) {
      $visibility = $word->visibility ? t('Only on') . ': ' : t('Except') . ': ';
    }
    $options[$word->id] = array(
      'id' => $delta,
      'text' => check_plain($word->text),
      'case_sensitive' => $word->case_sensitive ? t('Yes') : t('No'),
      'url' => array(
        'data' => array(
          '#type' => 'link',
          '#title' => check_plain($word->url),
          '#href' => $word->url,
          '#options' => array('attributes' => array('target' => '_blank')),
        ),
      ),
      'url_title' => check_plain($word->url_title),
      'class' => check_plain($word->class),
      'except' => $visibility . str_replace(array("\r\n", "\n", "\r"), " | ", check_plain(trim($word->except))),
    );

    // Build a list of all the accessible operations for the current word.
    $operations = array();
    if (user_access('edit word link')) {
      $operations['edit'] = array(
        'title' => t('edit'),
        'href' => 'admin/config/content/word-link/edit/' . $word->id,
        'query' => $destination,
      );
    }
    if (user_access('delete word link')) {
      $operations['delete'] = array(
        'title' => t('delete'),
        'href' => 'admin/config/content/word-link/delete/' . $word->id,
        'query' => $destination,
      );
    }
    if (count($operations) > 1) {
      // Render an unordered list of operations links.
      $options[$word->id]['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        ),
      );
    }
    elseif (!empty($operations)) {
      // Render the first and only operation as a link.
      $link = reset($operations);
      $options[$word->id]['operations'] = array(
        'data' => array(
          '#type' => 'link',
          '#title' => $link['title'],
          '#href' => $link['href'],
          '#options' => array('query' => $link['query']),
        ),
      );
    }
  }
  // Only use a tableselect when the current user is able to perform any
  // operations.
  if (user_access('edit word link') || user_access('delete word link')) {
    $form['words'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('No words available.'),
    );
  }
  // Otherwise, use a simple table.
  else {
    unset($header['operations']);
    $form['words'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $options,
      '#empty' => t('No words available.'),
    );
  }
  if (!empty($options) && user_access('delete word link')) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Delete selected'),
    );
  }
  $form['pager'] = array('#theme' => 'pager');

  return $form;
}

/**
 * Submit for a Word Link list page.
 */
function word_link_list_page_form_submit($form, &$form_state) {
  $form_state['rebuild'] = TRUE;
}

/**
 * Confirm form for delete operation.
 */
function word_link_list_page_form_delete_confirm($form, &$form_state, $words) {
  $form['words']['#tree'] = TRUE;
  foreach ($words as $wid) {
    $form['words'][$wid] = array(
      '#type' => 'hidden',
      '#value' => $wid,
    );
  }
  $form['#submit'][] = 'word_link_list_page_form_delete_confirm_submit';
  $confirm_question = format_plural(count($words), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  return confirm_form($form, $confirm_question, 'admin/config/content/word-link/list', t('This action cannot be undone.'), t('Delete'), t('Cancel'));
}

/**
 * Submit for delete operation.
 */
function word_link_list_page_form_delete_confirm_submit($form, &$form_state) {
  foreach ($form_state['values']['words'] as $wid) {
    if (!empty($wid) && is_numeric($wid)) {
      word_link_del_link($wid);
    }
  }
}

/**
 * Form builder for add or edit page.
 */
function word_link_add_form($form, &$form_state, $id = NULL) {
  $form['word_link_add_form'] = array(
    '#type' => 'details',
    '#title' => t('Add word'),
    '#collapsed' => FALSE,
  );
  if (!empty($id)) {
    $defaults = word_link_get_link($id);
    $form['word_link_add_form']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#weight' => 15,
      '#submit' => array('word_link_add_form_delete_submit'),
    );
  }
  $form['word_link_add_form']['text'] = array(
    '#type' => 'textfield',
    '#title' => t('Word/Phrase'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('The word or phrase you wish to convert to a link. This field is case sensitive.'),
    '#required' => TRUE,
    '#default_value' => !empty($defaults[$id]->text) ? $defaults[$id]->text : '',
  );
  $form['word_link_add_form']['case_sensitive'] = array(
    '#type' => 'checkbox',
    '#title' => t('Case Sensitivity'),
    '#description' => t('By default Word Link are case sensitive. Uncheck this checkbox if you want this particular Word Link to be case insensitive.'),
    '#default_value' => isset($defaults[$id]->case_sensitive) ? (int) $defaults[$id]->case_sensitive : 1,
  );
  $form['word_link_add_form']['url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('The URL of the page to link to. External links must start with %http or %https and will be open in new window.', array('%http' => 'http://', '%https' => 'https://')),
    '#required' => TRUE,
    '#default_value' => isset($defaults[$id]->url) ? $defaults[$id]->url : '',
  );
  $form['word_link_add_form']['url_title'] = array(
    '#type' => 'textfield',
    '#title' => t('URL Title'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('Title for the above URL. It will be embedded in the created link and appear as a tooltip when hovering the mouse over the link.'),
    '#default_value' => isset($defaults[$id]->url_title) ? $defaults[$id]->url_title : '',
  );
  $form['word_link_add_form']['class'] = array(
    '#type' => 'textfield',
    '#title' => t('Class'),
    '#size' => 30,
    '#maxlength' => 255,
    '#description' => t('Use this to add a class for the link. Default value is "word-link".'),
    '#default_value' => isset($defaults[$id]->class) ? $defaults[$id]->class : 'word-link',
  );
  $form['word_link_add_form']['visibility'] = array(
    '#type' => 'radios',
    '#title' => t('Show links on specific pages'),
    '#options' => array(
      0 => t('All pages except those listed'),
      1 => t('Only the listed pages'),
    ),
    '#default_value' => isset($defaults[$id]->visibility) ? $defaults[$id]->visibility : 0,
  );
  $form['word_link_add_form']['except'] = array(
    '#type' => 'textarea',
    '#description' => t('Specify pages by using their paths. Enter one path per line. E.g. node/1.'),
    '#default_value' => isset($defaults[$id]->except) ? $defaults[$id]->except : '',
  );
  $form['word_link_add_form']['id'] = array(
    '#type' => 'hidden',
    '#value' => !empty($id) ? $id : NULL,
  );
  $form['word_link_add_form']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 15,
    '#submit' => array('word_link_add_form_save_submit'),
  );

  return $form;
}

/**
 * Validate form for add or edit page.
 */
function word_link_add_form_validate($form, &$form_state) {
  if ($form_state['values']['op'] == t('Save')) {
    $args = arg();
    $arg = empty($args[5]) ? 0 : $args[5];
    $exist = word_link_get_link_text(trim($form_state['values']['text']), $arg);
    if ($exist) {
      form_set_error('text', t('This word already exists.'));
    }
    if (!drupal_valid_path($form_state['values']['url'])) {
      form_set_error('url', t("The path '@url' is either invalid or you do not have access to it.", array('@url' => $form_state['values']['url'])));
    }
    if (!empty($form_state['values']['class']) && !preg_match('/^[_a-zA-Z]+[_a-zA-Z0-9-\s]*$/', trim($form_state['values']['class']))) {
      form_set_error('class', t('Class is not valid.'));
    }
  }
}

/**
 * Submit for delete action.
 */
function word_link_add_form_delete_submit($form, &$form_state) {
  $destination = array();
  if (isset($_GET['destination'])) {
    $destination = drupal_get_destination();
    unset($_GET['destination']);
  }
  $form_state['redirect'] = array('admin/config/content/word-link/delete/' . $form_state['values']['id'], array('query' => $destination));
}

/**
 * Submit for save action.
 */
function word_link_add_form_save_submit($form, &$form_state) {
  $values = array(
    'text' => trim($form_state['values']['text']),
    'case_sensitive' => $form_state['values']['case_sensitive'],
    'url' => trim($form_state['values']['url']),
    'url_title' => trim($form_state['values']['url_title']),
    'class' => trim($form_state['values']['class']),
    'visibility' => $form_state['values']['visibility'],
    'except' => trim($form_state['values']['except']),
  );
  if (isset($form_state['values']['id'])) {
    word_link_add_update_link('update', $values, $form_state['values']['id']);
    drupal_set_message(t('Done'));
  }
  else {
    word_link_add_update_link('insert', $values);
    drupal_set_message(t('Link succesfuly added'));
  }
  $form_state['redirect'] = 'admin/config/content/word-link/list';
}

/**
 * Form builder for delete confirm page.
 */
function word_link_del_form($form, &$form_state, $id) {
  $form['word_link_del_form']['message'] = array(
    '#markup' => t('This action cannot be undone.'),
    '#prefix' => '<div class="form-item">',
    '#suffix' => '</div>',
  );
  $form['word_link_del_form']['delete'] = array(
    '#type' => 'submit',
    '#value' => t('Delete'),
  );
  $form['word_link_del_form']['cancel'] = array(
    '#markup' => l(t('Cancel'), 'admin/config/content/word-link/list'),
  );
  $form['word_link_del_form']['id'] = array(
    '#type' => 'hidden',
    '#value' => $id,
  );

  return $form;
}

/**
 * Submit form for delete confirm page.
 */
function word_link_del_form_submit($form, &$form_state) {
  word_link_del_link($form_state['values']['id']);
  $form_state['redirect'] = 'admin/config/content/word-link/list';
}
