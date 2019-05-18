<?php

/**
 * @file
 * Contains admin page callbacks, form validation, and form submission handlers.
 */

/**
 * Page callback: Displays recipe nodes in various formats.
 *
 * @see recipe_menu()
 */
function recipe_export_multi($type = NULL) {

  drupal_set_title(t('Recipe bulk export'));

  // load supported formats
  $formats = module_invoke_all('recipeio', 'export_multi');
  $o = t('Supported bulk output formats:');
  if ( $type === NULL ) {
    foreach ( $formats as $key => $format ) {
      $format_count = 0;
      if ($format) {
        $o .= '<br/>' . l($format['format_name'], "admin/structure/recipe/export_multi/$key");
        $format_count++;
      }
    }
    if ( $format_count == 0 ) {
      $o .= '<br/><p>' . t('You have no export formats available with the bulk export feature.') . '</p>';
    }
    return $o;
  }

  // normalize typed urls
  $type = drupal_strtolower($type);

  // If callback exists, call it, otherwise error out.
  if (isset($formats[$type]) && function_exists($formats[$type]['callback'])) {
    $o = call_user_func($formats[$type]['callback']);
    return $o;
  }
  else {
    drupal_set_message(t('Unknown export format(%the_format).', array('%the_format' => $type)), 'error');
    drupal_not_found();
  }
}

/**
 * Page callback: Constructs a form for importing a single recipe.
 *
 * @see recipe_menu()
 * @see recipe_import_form_build_preview()
 * @see recipe_import_form_validate()
 * @see recipe_import_form_submit()
 */
function recipe_import_form($form, &$form_state) {
  $formats = module_invoke_all('recipeio', 'import_single');

  $options = array();
  foreach ($formats as $format) {
    $options[$format['callback']] = $format['format_name'];
  }

  // Some special stuff when previewing a node.
  if (isset($form_state['node_preview'])) {
    $form['#prefix'] = $form_state['node_preview'];
  }

  $form['recipe_format'] = array(
    '#type' => 'select',
    '#title' => t('Recipe format'),
    '#options' => $options,
    '#default_value' => !empty($form_state['values']['recipe_format']) ? $form_state['values']['recipe_format'] : '',
    '#size' => 1,
    '#description' => t('The recipe input format.'),
  );
  $form['recipe_import_text'] = array(
    '#type' => 'textarea',
    '#title' => t('Paste import data here'),
    '#default_value' => !empty($form_state['values']['recipe_import_text']) ? $form_state['values']['recipe_import_text'] : '',
    '#cols' => 55,
    '#rows' => 8,
    '#required' => TRUE,
    '#description' => t('Use 1 blank line between sections: Description, Ingredients, Instructions, Notes. Always use preview first to avoid unintended consequences.'),
    '#attributes' => array('class' => array('recipe-import-text')),
  );
  $form['buttons']['preview'] = array(
    '#type' => 'submit',
    '#value' => t('Preview'),
    '#weight' => 1,
    '#submit' => array('recipe_import_form_build_preview'),
  );
  $form['buttons']['import'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
    '#weight' => 2,
    '#submit' => array('recipe_import_form_submit'),
  );

  return $form;
}

/**
 * Form submission handler for recipe_import_form() 'Preview' button.
 *
 * Import preview routine that allows that users to see what actually will be
 * imported before doing so.
 */
function recipe_import_form_build_preview($form, &$form_state) {
  drupal_add_css(drupal_get_path('module', 'recipe') . '/recipe.css');

  $parsed_recipe_object = recipe_import_parse($form, $form_state);
  if ($parsed_recipe_object != FALSE) {
    //$node = node_form_submit_build_node($form, $form_state);
    $node = recipe_import_get_node($parsed_recipe_object);
    $cloned_node = clone($node);
    $cloned_node->in_preview = 1;
    $form_state['node_preview'] = theme('node_preview', array('node' => $cloned_node));
    $form_state['rebuild'] = TRUE;
    drupal_set_title(t('Preview'));
  }
}

/**
 * Form validation handler for recipe_import_form().
 *
 * @see recipe_import_form_submit()
 */
function recipe_import_form_validate($form, &$form_state) {
  // Make sure that they choose an import format.
  // Otherwise the text entry is lost and the import fails with an error.
  if (empty($form_state['values']['recipe_format'])) {
    form_set_error('recipe_format', t('You must choose a recipe import format.'));
  }
}

/**
 * Form submission handler for recipe_import_form().
 *
 * @see recipe_import_form_validate()
 */
function recipe_import_form_submit($form, &$form_state) {
  global $user;
  $parsed_recipe_object = recipe_import_parse($form, $form_state);
  if (($node = recipe_import_get_node($parsed_recipe_object)) != FALSE) {
    node_save($node);
    $form_state['redirect'] = 'node/' . $node->nid . '/edit';
    drupal_set_message(t('Recipe Imported'));
  }
}

/**
 * Page callback: Displays bulk recipe import forms.
 *
 * @see recipe_menu()
 */
function recipe_import_multi($type = NULL) {
  drupal_set_title(t('Recipe bulk import'));

  // load supported formats
  $formats = module_invoke_all('recipeio', 'import_multi');
  $o = t('Supported bulk input formats:');
  if ( $type === NULL ) {
    $format_count = 0;
    foreach ( $formats as $key => $format ) {
      if ($format) {
        $o .= '<br/>' . l($format['format_name'], "admin/structure/recipe/import_multi/$key");
        $format_count++;
      }
    }
    if ( $format_count == 0 ) {
      $o .= '<br/><p>' . t('You have no import formats available with the bulk export feature.') . '</p>';
    }
    return $o;
  }

  // normalize typed urls
  $type = drupal_strtolower($type);

  // If callback exists, call it, otherwise error out.
  if (isset($formats[$type]) && function_exists($formats[$type]['callback'])) {
    $o = call_user_func($formats[$type]['callback']);
    return $o;
  }
  else {
    drupal_set_message(t('Unknown export format(%the_format).', array('%the_format' => $type)), 'error');
    drupal_not_found();
  }
}

/**
 * Returns a node-like stdClass object suitable for node_save and preview.
 */
function recipe_import_get_node($parsed_recipe_object = FALSE) {
  global $user;
  if ($parsed_recipe_object) {
    //node stuff
    $node = new stdClass();
    $node->type = 'recipe';
    $node->nid = NULL;
    node_object_prepare($node);
    $node->title = $parsed_recipe_object['title'];
    $node->language = LANGUAGE_NONE;

    //recipe stuff

    $node->recipe_description[LANGUAGE_NONE][0] = array(
      'value' => $parsed_recipe_object['title'] . ' imported from Recipe Import',
      'format' => 'filtered_html',
    );
    $node->recipe_instructions[LANGUAGE_NONE][0] = array(
      'value' => $parsed_recipe_object['instructions'],
      'format' => 'filtered_html',
    );
    $node->recipe_notes[LANGUAGE_NONE][0] = array(
      'value' => isset($parsed_recipe_object['notes']) ? $parsed_recipe_object['notes'] : '',
      'format' => 'filtered_html',
    );
    $node->recipe_source[LANGUAGE_NONE][0] = array(
      'value' => $parsed_recipe_object['source'] != '' ? $parsed_recipe_object['source'] : $user->name,
      'format' => 'filtered_html',
    );
    $node->recipe_prep_time[LANGUAGE_NONE][0] = array(
      'value' => $parsed_recipe_object['preptime'] != '' ? $parsed_recipe_object['preptime'] : 30,
    );
    $node->recipe_cook_time[LANGUAGE_NONE][0] = array(
      'value' => $parsed_recipe_object['cooktime'] != '' ? $parsed_recipe_object['cooktime'] : 30,
    );

    $node->recipe_yield = $parsed_recipe_object['yield'];
    $node->recipe_yield_unit = $parsed_recipe_object['yield_unit'];

    $delta = 0;
    $node->recipe_ingredient = array();
    foreach ($parsed_recipe_object['ingredients'] as $i) {
      $node->recipe_ingredient[LANGUAGE_NONE][$delta] = array(
        'iid' => recipe_ingredient_id_from_name($i['ingredient_name']),
        'quantity' => $i['quantity'],
        'unit_key' => $i['unit_key'],
        'note' => isset($i['ingredient_note']) ? $i['ingredient_note'] : '',
      );
      $delta++;
    }

    return $node;
  }
  return FALSE;
}

/**
 * Returns a parsed imported recipe based on the recipe_format.
 *
 * All parser instances should return a $recipe object that looks like this:
 *
 * $recipe = array(
 *   'title' => 'recipe title string',
 *   'ingredients' => array of ingredients items(below);
 *   'instructions' => 'string of instructions'
 * );
 *
 * ingredients items = array(
 *    'quantity' =>
 *    'ingredient_name' =>
 *    'unit_name' =>
 *    'unit_key' => see recipe_unit_fuzzymatch().  ==FALSE if no-match
 *    'ingre_obj' => comes from database lookup: see recipe_ingredient_match().  ==FALSE if no-match
 * );
 */
function recipe_import_parse($form, &$form_state) {
  $import_function = $form_state['values']['recipe_format'];
  $text = $form_state['values']['recipe_import_text'];

  $recipe = array();
  if (function_exists($import_function)) {
    $recipe = call_user_func($import_function, $text);
    return $recipe;
  }
  else {
    drupal_set_message(t('Recipe import function does not exist(%the_function)', array('%the_function' => $import_function)), 'error');
    return FALSE;
  }
}

/**
 * Returns the ID and name of an existing ingredient.
 *
 * @param string $recipe_ingredient_name
 *   A recipe_ingredient_name.
 *
 * @return array
 *   A recipe_ingredient array upon successful load or FALSE
 */
function recipe_ingredient_match($recipe_ingredient_name) {
  $result = db_query("SELECT id, name FROM {recipe_ingredient} where name = :name", array(':name' => $recipe_ingredient_name));
  foreach ($result as $row) {
    return array(
      'id' => $row->id,
      'name' => $row->name
    );
  }
  return FALSE;
}
