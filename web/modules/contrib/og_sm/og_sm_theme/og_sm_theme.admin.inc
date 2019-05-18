<?php
/**
 * @file
 * Admin callbacks for the theme settings.
 */

/**
 * Menu callback; displays a listing of all themes for a Site.
 *
 * Similar to Core, but only displays the enabled theme groups. We assume
 * all themes will be enabled globally before we allow them to be used in a
 * Site. Enabling and disabling is not supported from the Site theme settings.
 * Same goes for the incompatibility checks, we assume they passed when the
 * theme was enabled globally.
 *
 * @param object $site
 *   The Site.
 *
 * @return string
 *   Rendered themes overview
 *
 * @see system_themes_page()
 */
function og_sm_theme_themes_page($site) {
  $parent = 'group/node/' . $site->nid . '/admin/theme';
  og_sm_set_breadcrumb($site, array(l(t('Administer theme'), $parent)));

  // Get a list of available themes.
  $themes = og_sm_theme_site_themes($site);

  $theme_site = og_sm_theme_get_site_theme($site);
  $theme_groups  = array();

  foreach ($themes as &$theme) {
    if (!empty($theme->info['hidden'])) {
      continue;
    }

    $theme->is_default = ($theme->name == $theme_site);

    // Identify theme screenshot.
    $theme->screenshot = NULL;
    // Create a list which includes the current theme and all its base themes.
    if (isset($themes[$theme->name]->base_themes)) {
      $theme_keys = array_keys($themes[$theme->name]->base_themes);
      $theme_keys[] = $theme->name;
    }
    else {
      $theme_keys = array($theme->name);
    }
    // Look for a screenshot in the current theme or in its closest ancestor.
    foreach (array_reverse($theme_keys) as $theme_key) {
      if (!isset($themes[$theme_key])) {
        continue;
      }
      if (!file_exists($themes[$theme_key]->info['screenshot'])) {
        continue;
      }
      $theme_name = $theme->info['name'];
      $theme->screenshot = array(
        'path' => $themes[$theme_key]->info['screenshot'],
        'alt' => t('Screenshot for !theme theme', array('!theme' => $theme_name)),
        'title' => t('Screenshot for !theme theme', array('!theme' => $theme_name)),
        'attributes' => array('class' => array('screenshot')),
      );
      break;
    }

    $query['token'] = drupal_get_token('og-sm-theme-operation-link');
    $theme->operations = array();
    if (!empty($theme->status)) {
      // Create the operations links.
      $query['theme'] = $theme->name;
      if (drupal_theme_access($theme)) {
        $theme->operations[] = array(
          'title' => t('Settings'),
          'href' => $parent . '/settings/' . $theme->name,
          'attributes' => array('title' => t('Settings for !theme theme', array('!theme' => $theme->info['name']))),
        );
      }
      if (!empty($theme->status)) {
        if (!$theme->is_default) {
          $theme->operations[] = array(
            'title' => t('Set as site theme'),
            'href' => $parent . '/default',
            'query' => $query,
            'attributes' => array(
              'title' => t('Set !theme as site theme', array('!theme' => $theme->info['name'])),
            ),
          );
        }
      }
    }

    // Add notes to default and administration theme.
    $theme->notes = array();
    $theme->classes = array();
    if ($theme->is_default) {
      $theme->classes[] = 'theme-default';
      $theme->notes[] = t('default theme');
    }

    // Sort enabled and disabled themes into their own groups.
    $theme_groups[$theme->status ? 'enabled' : 'disabled'][] = $theme;
  }

  // There is just one possible theme group for Site themes, but since we reuse
  // Drupal's theme function we keep the array structure.
  $theme_group_titles = array(
    'enabled' => format_plural(count($theme_groups['enabled']), 'Site theme', 'Site themes'),
  );

  module_load_include('inc', 'system', 'system.admin');
  uasort($theme_groups['enabled'], 'system_sort_themes');

  drupal_alter('og_sm_theme_themes_page', $theme_groups);

  return theme('system_themes_page', array(
    'theme_groups' => $theme_groups,
    'theme_group_titles' => $theme_group_titles,
  ));
}

/**
 * Menu callback; Set the default theme.
 *
 * Similar to Drupal's callback but sets a Site variable instead of the global
 * default theme.
 *
 * @param object $site
 *   The Site.
 *
 * @return int|void
 *   Menu callback response or void on redirect
 *
 * @see system_theme_default()
 */
function og_sm_theme_set_default($site) {
  if (!isset($_REQUEST['theme']) || !isset($_REQUEST['token'])) {
    return MENU_ACCESS_DENIED;
  }

  if (!drupal_valid_token($_REQUEST['token'], 'og-sm-theme-operation-link')) {
    return MENU_ACCESS_DENIED;
  }

  $theme = $_REQUEST['theme'];

  // Get all themes that we can choose from.
  $themes = og_sm_theme_site_themes($site);

  // Check if the specified theme is one recognized by the system.
  if (!empty($themes[$theme])) {
    // Set the default theme.
    og_sm_variable_set($site->nid, 'theme', $theme);

    drupal_set_message(t('%theme is now the default theme.', array(
      '%theme' => $themes[$theme]->info['name'],
    )));

  }
  else {
    drupal_set_message(t('The %theme theme was not found.', array(
      '%theme' => $theme,
    )), 'error');
  }
  drupal_goto('group/node/' . $site->nid . '/admin/theme');
}

/**
 * Global theme configuration form.
 *
 * @return array $form
 *   The form
 */
function og_sm_theme_global_settings_form() {
  $form['available_themes'] = array(
    '#type' => 'fieldset',
    '#title' => t('Allowed themes'),
    '#description' => t('Select which themes should be available for Sites.'),
  );

  // Get a sorted list of enabled themes.
  $themes = array_filter(list_themes(), function ($theme) {
    return $theme->status;
  });

  // Sort the list.
  module_load_include('inc', 'system', 'system.admin');
  uasort($themes, 'system_sort_modules_by_info_name');

  // Map the keys with the human readable name.
  $themes = array_map(
    function ($theme) {
      return $theme->info['name'];
    }, $themes
  );

  $form['available_themes']['themes'] = array(
    '#type' => 'checkboxes',
    '#options' => $themes,
    '#default_value' => variable_get('og_sm_theme_site_themes', array()),
  );

  $global_theme = variable_get('theme_default', 'bartik');
  $form['default_theme'] = array(
    '#type' => 'select',
    '#options' => $themes,
    '#title' => t('Default theme'),
    '#description' => t('Choose the default Site theme to use when no theme has been selected.'),
    '#default_value' => variable_get('og_sm_theme_default_theme', $global_theme),
  );

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
}

/**
 * Form submit handler for the theme settings form.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 */
function og_sm_theme_global_settings_form_submit($form, $form_state) {
  variable_set('og_sm_theme_default_theme', $form_state['values']['default_theme']);

  $current_themes = variable_get('og_sm_theme_site_themes', array());
  $new_themes = $form_state['values']['themes'];

  variable_set('og_sm_theme_site_themes', $new_themes);

  // See if some sites are still using a theme that's not allowed anymore.
  $diff = array_diff(
    array_filter($current_themes),
    array_filter($new_themes)
  );
  if ($diff) {
    _og_sm_theme_verify_disabled_site_themes($diff);
  }

  drupal_set_message(t('The configuration options have been saved.'));
}

/**
 * Form for configuring theme settings within a Site.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 * @param object $site
 *   The Site object.
 * @param string $theme_key
 *   The theme key.
 *
 * @return array $form
 *   The settings form.
 *
 * @see system_theme_settings()
 */
function og_sm_theme_site_settings_form(array $form, array &$form_state, $site, $theme_key) {
  // Save these in form_state for later.
  $form_state['site'] = $site;
  $form_state['theme_key'] = $theme_key;

  $settings = og_sm_theme_get_theme_settings($site, $theme_key);

  // Custom logo link.
  $form['logo'] = array(
    '#type' => 'fieldset',
    '#title' => t('Logo link'),
    '#description' => t('The link behind the logo will point by default to the Site front page. You can override that location to an (external) URL.'),
  );
  $form['logo']['toggle_logo_link'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use a custom logo link'),
    '#default_value' => $settings['toggle_logo_link'],
  );
  $form['logo']['link'] = array(
    '#type' => 'container',
    '#states' => array(
      'visible' => array(
        'input[name="toggle_logo_link"]' => array('checked' => TRUE),
      ),
    ),
    '#attributes' => array(
      'style' => 'display: none;',
    ),
  );
  $form['logo']['link']['logo_link_url'] = array(
    '#type' => 'textfield',
    '#title' => t('URL'),
    '#description' => t('Internal path (eg. site-path/idea) or external URL (eg. http://google.com/123).'),
    '#default_value' => $settings['logo_link_url'],
    '#element_validate' => array('og_sm_theme_site_settings_form_validate_custom_logo_link_url'),

  );
  $form['logo']['link']['logo_link_target'] = array(
    '#type' => 'select',
    '#title' => t('Link target'),
    '#description' => t('Select how the link should open in the browser.'),
    '#options' => array(
      '_self' => t('Current window/tab'),
      '_blank' => t('New window/tab'),
    ),
    '#default_value' => $settings['logo_link_target'],
    '#chosen' => FALSE,
  );

  // Toggle settings, was used for breadcrumbs, leave empty for now.
  $toggles = array();
  if (count($toggles)) {
    $form['theme_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Toggle display'),
      '#description' => t('Enable or disable the display of certain page elements.'),
    );
    foreach ($toggles as $name => $title) {
      $form['theme_settings']['toggle_' . $name] = array(
        '#type' => 'checkbox',
        '#title' => $title,
        '#default_value' => $settings['toggle_' . $name],
      );
    }
  }

  $form = system_settings_form($form);
  // We don't want to call system_settings_form_submit(), so change #submit.
  $form['#submit'] = array('og_sm_theme_site_settings_form_submit');

  return $form;
}

/**
 * Form submit handler for the Site theme settings form.
 *
 * @param array $form
 *   The form.
 * @param array $form_state
 *   The form state.
 */
function og_sm_theme_site_settings_form_submit(array $form, array $form_state) {
  form_state_values_clean($form_state);
  $values     = $form_state['values'];
  $site       = $form_state['site'];
  $theme_key  = $form_state['theme_key'];

  og_sm_theme_set_theme_settings($values, $site, $theme_key);
  og_sm_site_cache_clear_all($site);

  drupal_set_message(t('The theme settings have been saved.'));
}

/**
 * Form element validator for the custom_home_url field.
 *
 * @param array $element
 *   The element to validate.
 * @param array $form_state
 *   The form state.
 * @param array $form
 *   The form structure.
 */
function og_sm_theme_site_settings_form_validate_custom_logo_link_url(array $element, array &$form_state, array $form) {
  $is_custom = !empty($form_state['values']['toggle_custom_logo_link']);
  $value = trim($element['#value']);

  // Don't care if nothing is set.
  if (!$is_custom && empty($value)) {
    return;
  }

  // If we got here, a custom URL is required.
  if (empty($value)) {
    form_error(
      $element,
      t('The custom logo URL is missing.')
    );
    return;
  }

  // Is external URL?
  if (url_is_external($value)) {
    return;
  }

  // Is valid Drupal path?
  $path_source = drupal_get_normal_path($value);
  if ($path_source && drupal_valid_path($path_source, FALSE)) {
    return;
  }

  // The provided URL is not valid.
  form_error(
    $element,
    t('The URL should be either a valid external URL or an existing internal path.')
  );
}
