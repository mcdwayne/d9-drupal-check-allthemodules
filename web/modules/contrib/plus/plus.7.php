<?php

/**
 * @file
 * Drupal 7 module hooks/alters for the Drupal+ module.
 */

use Drupal\plus\Utility\Element;

/**
 * Implements hook_boot().
 */
function plus_boot() {
  // Because drupal_theme_initialize() is invoked before hook_init(), it adds
  // any theme JavaScript using drupal_add_js(), which in turn invokes
  // drupal_add_library() to add jQuery to the page. In order to prevent any
  // premature invocation of drupal_add_library(), set the static now and
  // just go ahead and define the default drupalSettings which is a backport
  // from Drupal 8.
  $javascript = &drupal_static('drupal_add_js', []);
  drupal_static('drupal_add_js:jquery_added', TRUE);
  $javascript = drupal_array_merge_deep($javascript, [
    'settings' => [
      'data' => [],
      'type' => 'setting',
      'scope' => 'header',
      'scope_lock' => TRUE,
      'group' => JS_CORE,
      'every_page' => TRUE,
    ],
  ]);
}

/**
 * Implements hook_page_delivery_callback_alter().
 *
 * {@inheritdoc}
 */
function plus_page_delivery_callback_alter(&$callback) {
  if (variable_get('plus_deliver_html_drupal8', TRUE)) {
    switch ($callback) {
      case 'drupal_deliver_html_page':
        $callback = 'plus_deliver_html_page';
        break;

      case 'ajax_deliver':
        $callback = 'plus_ajax_deliver';
        break;
    }
  }
}

/**
 * Delivers an HTML response.
 *
 * @param $page_callback_result
 *   The page callback result.
 *
 * @todo Use Drupal 8 HTML delivery to take advantage of render array and
 * library/asset improvements.
 */
function plus_deliver_html_page($page_callback_result) {
  drupal_deliver_html_page($page_callback_result);
}

/**
 * Delivers an Ajax response.
 *
 * @param $page_callback_result
 *   The page callback result.
 *
 * @todo Use Drupal 8 ajax delivery to take advantage of render array and
 * library/asset improvements.
 */
function plus_ajax_deliver($page_callback_result) {
  ajax_deliver($page_callback_result);
}

/**
 * Callback for dynamically invoking Drupal 7 #pre_render callbacks.
 *
 * @param array $element
 *   The render array element.
 *
 * @return array
 *   The modified render array element.
 *
 * @see \Drupal\plus\Utility\Element::addCallback()
 */
function plus_element_pre_render_callback(array $element) {
  $callbacks = Element::reference($element)->getProperty('plus_pre_render', []);
  foreach ($callbacks as $callback) {
    $element = call_user_func_array($callback, [$element]);
  }
  return $element;
}

/**
 * Implements hook_js_settings_alter().
 */
function plus_js_settings_alter(array &$settings) {
  global $language;
  global $theme;

  // url() generates the prefix using hook_url_outbound_alter(). Instead of
  // running the hook_url_outbound_alter() again here, extract the prefix
  // from url().
  $path_prefix = '';
  url('', ['prefix' => &$path_prefix]);

  $base_path = base_path();
  $current_path = current_path();

  $path_settings = [
    'baseUrl' => $base_path,
    'pathPrefix' => $path_prefix,
    'currentPath' => $current_path,
    'currentPathIsAdmin' => path_is_admin($current_path),
    'isFront' => drupal_is_front_page(),
    'currentLanguage' => $language->language,
  ];

  // Only set values that haven't been set already.
  foreach ($path_settings as $key => $value) {
    if (!isset($settings['path'][$key])) {
      $settings['path'][$key] = $value;
    }
  }

  // Add the theme token to ajaxPageState, ensuring the database is available
  // before doing so. Also add the loaded libraries to ajaxPageState.
  $libraries = plus_get_loaded_libraries();
  if (isset($settings['ajaxPageState']) || in_array('system/drupal.ajax', $libraries)) {
    // Provide the page with information about the theme that's used, so that
    // a later AJAX request can be rendered using the same theme.
    $settings['ajaxPageState']['theme'] = $theme;

    // The theme token is only validated when the theme requested is not the
    // default, so don't generate it unless necessary.
    if (!defined('MAINTENANCE_MODE') && $theme !== variable_get('theme_default', 'bartik')) {
      $settings['ajaxPageState']['theme_token'] = drupal_get_token($theme);
    }

    $settings['ajaxPageState']['libraries'] = $libraries;
  }
}

function plus_init_libraries() {

  // Unfortunately, in Drupal 7, hook_library() and hook_library_alter() do not
  // conform to the "normal" hook build/alter pattern. Nor are these hook info
  // definitions cached (at all). Every time either the drupal_add_library() or
  // drupal_get_library() functions are invoked, it starts the manual process
  // of retrieving and altering each module's libraries separately. This can
  // cause serious issues if a module needs to do some heavy computational
  // building or alters of library definitions. It also prevents a module from
  // altering all libraries, regardless of where they were defined, without
  // recursion issues (e.g. calling drupal_get_library() inside alters).
  $cid = 'plus:library_info';
  $libraries = &drupal_static('drupal_get_library', []);

  // Immediately set cached library info and return, if it exists.
  if (($cache = cache_get($cid)) && isset($cache->data) && is_array($cache->data)) {
    $libraries = $cache->data;
    return;
  }

  // Get all module libraries.
  foreach (module_implements('library') as $module) {
    // Skip AdvAgg's implementation as it's not really a hook implementation.
    // @see https://www.drupal.org/project/advagg/issues/2946751
    if ($module === 'advagg') {
      continue;
    }
    $libraries[$module] = module_invoke($module, 'library');
    if (empty($libraries[$module])) {
      $libraries[$module] = [];
    }
  }

  // Perform each individual module library info alters (like core does).
  foreach ($libraries as $module => &$module_libraries) {
    drupal_alter('library', $module_libraries, $module);
  }

  // Now let modules perform an alter for all defined libraries.
  drupal_alter('all_libraries', $libraries);

  // Add default elements to allow for easier processing.
  foreach ($libraries as $module => &$module_libraries) {
    foreach ($module_libraries as $key => $data) {
      if (is_array($data)) {
        $module_libraries[$key] += [
          'dependencies' => [],
          'js' => [],
          'css' => [],
        ];
        foreach ($module_libraries[$key]['js'] as $file => $options) {
          $module_libraries[$key]['js'][$file]['version'] = $module_libraries[$key]['version'];
        }
      }
    }
  }

  // Cache the library info definitions.
  cache_set($cid, $libraries);
}

/**
 * Implements hook_init().
 */
function plus_init() {
  // Reset JavaScript's drupalSettings. During the bootstrap phase, the
  // drupal_theme_initialize() function adds the theme and theme token in the
  // "ajaxPageState" prematurely. These are added back, correctly, if so needed
  // in plus_js_settings_alter().
  $javascript = &drupal_static('drupal_add_js', []);
  $javascript['settings']['data'] = [];

//    Drupal::service('library_info');

  // Initialize libraries.
  plus_init_libraries();

  // Add jQuery and the Drupal libraries if the site should always use theme.
  if (variable_get('javascript_always_use_jquery', TRUE)) {
    drupal_add_library('system', 'jquery');
    drupal_add_library('system', 'jquery.once');
    drupal_add_library('system', 'drupal');
  }
}

/**
 * Implements hook_all_libraries_alter().
 *
 * @see plus_init()
 */
function plus_all_libraries_alter(&$libraries) {
  $module_path = drupal_get_path('module', 'plus');

  // Create a Drupal 8 backport of the "core/drupal" library, but using this
  // module's replacement instead.
  $libraries['system']['drupal'] = [
    'title' => 'Drupal+',
    'version' => '1.0.0',
    'es6' => FALSE,
    'js' => [
      $module_path . '/js/Drupal.js' => [
        'es6' => FALSE,
        'group' => JS_CORE,
      ],
    ],
  ];

  // Add the new Drupal library as a dependency for all core libraries that
  // start with "drupal.".
  foreach ($libraries['system'] as $key => &$info) {
    if (strpos($key, 'drupal.') === 0) {
      if (!isset($info['dependencies'])) {
        $info['dependencies'] = [];
      }
      array_unshift($info['dependencies'], ['system', 'drupal']);
    }
  }

  // Add a default placeholder for ajaxPageState.
  $libraries['system']['drupal.ajax']['js'][] = [
    'type' => 'setting',
    'data' => ['ajaxPageState' => []],
  ];

  // Add a jQuery On/Off shim if necessary.
  $jquery_on_off_shim = TRUE;
  if (module_exists('jquery_update')) {
    $jquery_versions = explode('.', variable_get('jquery_update_jquery_version', '1.10'));
    if ((int) $jquery_versions[0] > 1 || ((int) $jquery_versions[0] === 1 && (int) $jquery_versions[1] > 6)) {
      $jquery_on_off_shim = FALSE;
    }
  }
  if ($jquery_on_off_shim) {
    $libraries['system']['jquery']['js'][$module_path . '/js/jquery.on.off.shim.js'] = $libraries['system']['jquery']['js']['misc/jquery.js'];
    unset($libraries['system']['jquery']['js'][$module_path . '/js/jquery.on.off.shim.js']['data']);
  }

  foreach ($libraries as $module => &$module_libraries) {
    foreach ($module_libraries as $key => &$info) {
      if (is_array($info) && !empty($info['js']) && _plus_is_library_es6($info, $libraries)) {
        // Add a dependency on the Drupal+ Asset Manager, but only if the module
        // being processed is not this module, since doing so can cause
        // dependency recursion.
        if ($module !== 'plus') {
          $info['dependencies'][] = ['plus', 'drupal.asset.manager'];
        }

        // Mark all JavaScript files as ES6 since this the whole library is ES6
        // (which may be because of an ES6 dependency).
        foreach ($info['js'] as &$data) {
          $data['es6'] = TRUE;
        }
      }
    }
  }
}

function _plus_is_library_es6(array &$info, array &$libraries) {
  // Immediately return if ES6 support was already determined or explicitly set.
  if (isset($info['es6'])) {
    return $info['es6'];
  }

  $info['es6'] = FALSE;
  if (!empty($info['dependencies'])) {
    foreach ($info['dependencies'] as $dependency) {
      list($dependency_module, $dependency_name) = $dependency;
      $dependency = isset($libraries[$dependency_module][$dependency_name]) ? $libraries[$dependency_module][$dependency_name] : [];
      $info['es6'] = $dependency ? _plus_is_library_es6($dependency, $libraries) : FALSE;
      if ($info['es6']) {
        break;
      }
    }
  }

  // If ES6 couldn't be determined by dependencies, check if this library's JS
  // has explicitly set "es6" or the filename ends with ".es6.js".
  if (!$info['es6'] && !empty($info['js'])) {
    foreach ($info['js'] as $file => &$data) {
      $file = (!isset($data['type']) || $data['type'] === 'file') && isset($data['data']) ? $data['data'] : $file;
      if (!empty($data['es6']) || preg_match('/\.es6\.js/', $file)) {
        $info['es6'] = TRUE;
        break;
      }
    }
  }

  return $info['es6'];
}

function plus_core_js_files() {
  $module_path = drupal_get_path('module', 'plus');

  $files = [
    'misc/jquery.js',
    $module_path . '/js/jquery.on.off.shim.js',
    'misc/jquery.once.js',
    $module_path . '/js/Drupal.js',
    'misc/ajax.js',
  ];
  return $files;
}

/**
 * Implements hook_element_info_alter().
 */
function plus_element_info_alter(&$types) {
  if (!isset($types['scripts']['#pre_render'])) {
    $types['scripts']['#pre_render'] = [];
  }

  foreach ($types as $type => &$info) {
    if (!isset($info['#pre_render'])) {
      $info['#pre_render'] = [];
    }
    if ($type === 'scripts') {
      array_unshift($types['scripts']['#pre_render'], 'plus_pre_render_scripts');
    }
    else {
      array_unshift($types['scripts']['#pre_render'], 'plus_pre_render_drupal_settings');
    }
  }
}

function plus_pre_render_drupal_settings(array $element) {
  // Immediately return if there are no attachments.
  if (!isset($element['#attached'])) {
    return $element;
  }

  $settings = isset($element['#attached']['drupalSettings']) ? $element['#attached']['drupalSettings'] : [];
  unset($element['#attached']['drupalSettings']);

  if (isset($element['#attached']['js'])) {
    foreach ($element['#attached']['js'] as $key => $info) {
      if (is_array($info) && isset($info['type']) && $info['type'] === 'setting') {
        $settings = drupal_array_merge_deep($settings, drupal_array_merge_deep_array($info['data']));
        unset($element['#attached']['js'][$key]);
      }
    }
  }

  if ($settings) {
    $element['#attached']['js'][] = [
      'type' => 'setting',
      'data' => $settings,
    ];
  }

  return $element;
}

/**
 * Implements hook_library().
 */
function plus_library() {
  $module_path = drupal_get_path('module', 'plus');
  $version = '1.0.0';

  $libraries['drupal.asset'] = [
    'title' => 'Drupal+ Asset',
    'version' => $version,
    'es6' => TRUE,
    'js' => [
      $module_path . '/js/Drupal.Asset.es6.js' => [
        'es6' => TRUE,
        'group' => JS_CORE,
      ],
    ],
    'dependencies' => [
      ['system', 'drupal'],
      ['plus', 'drupal.url'],
    ],
  ];

  $libraries['drupal.asset.manager'] = [
    'title' => 'Drupal+ Asset Manager',
    'version' => $version,
    'es6' => TRUE,
    'js' => [
      $module_path . '/js/Drupal.AssetManager.es6.js' => [
        'es6' => TRUE,
        'group' => JS_CORE,
      ],
    ],
    'dependencies' => [
      ['plus', 'drupal.asset'],
    ],
  ];

  $libraries['drupal.async.queue'] = [
    'title' => 'Drupal+ Asynchronous Queue',
    'version' => $version,
    'es6' => TRUE,
    'js' => [
      $module_path . '/js/Drupal.AsyncQueue.es6.js' => [
        'group' => JS_CORE,
      ],
    ],
    'dependencies' => [
      ['system', 'drupal'],
    ],
  ];

  $libraries['drupal.storage'] = [
    'title' => 'Drupal+ Storage',
    'version' => $version,
    'es6' => TRUE,
    'js' => [
      $module_path . '/js/Drupal.Storage.es6.js' => [
        'es6' => TRUE,
        'group' => JS_LIBRARY,
      ],
    ],
    'dependencies' => [
      ['system', 'drupal'],
    ],
  ];

  $libraries['drupal.url'] = [
    'title' => 'Drupal+ URL',
    'version' => $version,
    'es6' => TRUE,
    'js' => [
      $module_path . '/js/Drupal.Url.es6.js' => [
        'es6' => TRUE,
        'group' => JS_LIBRARY,
      ],
    ],
    'dependencies' => [
      ['system', 'drupal'],
    ],
  ];

  return $libraries;
}

function &_plus_find_element_by_key(&$elements, $key) {
  $element = NULL;
  foreach (element_children($elements) as $child) {
    if ($child === $key) {
      $element = &$elements[$child];
      break;
    }
    if ($element = &_plus_find_element_by_key($elements[$child], $key)) {
      break;
    }
  }
  return $element;
}

/**
 * Implements hook_module_implements_alter().
 */
function plus_module_implements_alter(&$implementations, $hook) {
  // Move plus to the bottom of the following hooks.
  $alter_hooks = [
    'init' => 'before',
    'element_info_alter' => 'after',
    'js_alter' => 'after',
  ];
  foreach ($alter_hooks as $alter_hook => $position) {
    if ($hook === $alter_hook && array_key_exists('plus', $implementations)) {
      $item = $implementations['plus'];
      unset($implementations['plus']);
      if ($position === 'after') {
        $implementations['plus'] = $item;
      }
      else {
        $implementations = ['plus' => $item] + $implementations;
      }
    }
  }
}

/**
 * Implements hook_form_alter().
 */
function plus_form_alter(&$form, &$form_state, $form_id) {
  // Immediately return if advagg settings shouldn't be managed.
  if (!variable_get('plus_manage_advagg_mod_settings', TRUE)) {
    return;
  }

  // Map the form identifier to the manage variable.
  $map = [
    'advagg_admin_settings_form' => 'plus_manage_advagg_settings',
    'advagg_bundler_admin_settings_form' => 'plus_manage_advagg_bundler_settings',
    'advagg_js_compress_admin_settings_form' => 'plus_manage_advagg_compressor_settings',
    'advagg_mod_admin_settings_form' => 'plus_manage_advagg_mod_settings',
  ];

  if (isset($map[$form_id]) && variable_get($map[$form_id], TRUE)) {
    $recommended = t('(recommended)');
    $managed = t('(managed by <a href="!plus">Drupal+</a>)', [
      // @todo Change URL to an admin UI page.
      '!plus' => url('https://www.drupal.org/project/plus'),
    ]);

    $settings = plus_manage_advagg_settings();
    foreach ($settings as $name => $value) {
      if ($element = &_plus_find_element_by_key($form, $name)) {
        if (strpos($element['#title'], $recommended) !== FALSE) {
          $element['#title'] = str_replace($recommended, $managed, $element['#title']);
        }
        else {
          $element['#title'] .= ' ' . $managed;
        }
        $element['#default_value'] = $value;
        $element['#disabled'] = TRUE;
        unset($element['#states']);
      }
    }
  }

}

/**
 * Implements hook_advagg_mod_get_lists_alter().
 */
function plus_advagg_mod_get_lists_alter(&$data) {
  // AdvAgg doesn't really provide a decent way to alter $all_in_footer_list.
  $all_in_footer_list = &$data[6];

  // Ensure all the core files are scope locked to the header.
  foreach (plus_core_js_files() as $file) {
    if (!isset($all_in_footer_list[$file])) {
      $all_in_footer_list[$file] = [$file];
    }
  }
}

/**
 * Provides AdvAgg setting overrides.
 *
 * Forcefully sets runtime AdvAgg settings that are not conducive with this
 * module's operation. Typically, these settings are configured incorrectly
 * anyway due to the lack of understanding and knowledge of how JavaScript
 * execution actually works in a browser.
 *
 * Thus, AdvAgg does a lot of unnecessary "wrapping" and "onload" event
 * modifications to the scripts in an effort to be a "catch all" for said
 * site developer.
 *
 * This is counter productive though and causes issues with this module which
 * aims to standardize everything using backports from Drupal 8 and new
 * libraries like Drupal.AssetManager and Drupal.AsyncBehaviors.
 *
 * @return array
 *   An array of the overridden AdvAgg settings.
 */
function plus_manage_advagg_settings() {
  global $conf;

  $settings = [];

  // AdvAgg JavaScript Settings.
  if (variable_get('plus_manage_advagg_settings', TRUE)) {
    $settings = array_merge($settings, [
      'advagg_core_groups' => FALSE,
    ]);
  }

  // AdvAgg JavaScript Bundler Settings.
  if (variable_get('plus_manage_advagg_bundler_settings', TRUE)) {
    $settings = array_merge($settings, [
      'advagg_bundler_active' => TRUE,
    ]);
  }

  // AdvAgg JavaScript Compressor Settings.
  if (variable_get('plus_manage_advagg_compressor_settings', TRUE)) {
    $settings = array_merge($settings, [
      'advagg_js_compressor' => 1,
      'advagg_js_compress_inline' => 1,
      'advagg_js_compress_add_license' => 3,
    ]);
  }

  // AdvAgg JavaScript Modification Settings.
  if (variable_get('plus_manage_advagg_mod_settings', TRUE)) {
    $settings = array_merge($settings, [
      'advagg_mod_ga_inline_to_file' => FALSE,
      'advagg_mod_prefetch' => FALSE,
      'advagg_mod_js_adjust_sort_browsers' => FALSE,
      'advagg_mod_js_adjust_sort_external' => FALSE,
      'advagg_mod_js_adjust_sort_inline' => FALSE,
      'advagg_mod_js_async' => FALSE,
      'advagg_mod_js_async_in_header' => FALSE,
      'advagg_mod_js_async_shim' => FALSE,
      'advagg_mod_js_defer' => 0,
      'advagg_mod_js_defer_inline_alter' => FALSE,
      'advagg_mod_js_footer' => 3,
      'advagg_mod_js_footer_inline_alter' => FALSE,
      'advagg_mod_js_head_extract' => TRUE,
      'advagg_mod_js_no_ajaxpagestate' => FALSE,
      'advagg_mod_js_preprocess' => TRUE,
      'advagg_mod_js_remove_unused' => FALSE,
    ]);
  }

  // Override runtime config.
  foreach ($settings as $name => $value) {
    $conf[$name] = $value;
  }

  return $settings;
}

/**
 * Implements hook_js_alter().
 */
function plus_js_alter(&$javascript) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['es6'] = &drupal_static(__FUNCTION__ . ':es6', []);
    $drupal_static_fast['settings'] = &drupal_static(__FUNCTION__ . ':settings', []);

    // Override AdvAgg settings (only needs to be invoked once).
    plus_manage_advagg_settings();
  }
  $es6 = &$drupal_static_fast['es6'];
  $settings = &$drupal_static_fast['settings'];

  // Immediately return if this is rendering the ES6 files.
  if (!empty($javascript['es6_rendering'])) {
    unset($javascript['es6_rendering']);
    return;
  }

  // Capture all Drupal settings and remove them from the $javascript array.
  // They will be handled separately in plus_render_drupal_settings() as a
  // backport of Drupal 8's drupalSettings.
  foreach ($javascript as $key => $item) {
    if (isset($item['type']) && $item['type'] === 'setting') {
      $settings = array_merge($settings, isset($item['data']) && is_array($item['data']) ? $item['data'] : []);
      unset($javascript[$key]);
    }
  }

  $core_files = plus_core_js_files();

  // Find all ES6 JavaScript.
  foreach ($javascript as $key => $info) {
    $core_file = in_array($key, $core_files);

    // Move core files to their own group.
    if ($core_file) {
      $javascript[$key]['group'] = JS_CORE;
    }
    // Otherwise, defer the rest.
    elseif ($javascript[$key]['type'] === 'file' || $javascript[$key]['type'] === 'external') {
      $javascript[$key]['defer'] = TRUE;
    }

    // Remove ES6 files since this must must be conditionally loaded if the
    // browser supports ES6.
    if (!$core_file && (!empty($info['es6']) || ($info['type'] === 'file' && preg_match('/\.es6\.js/', $info['data'])))) {
      if (!isset($es6[$key])) {
        $es6[$key] = [];
      }
      $es6[$key] = drupal_array_merge_deep($es6[$key], $info);
      unset($javascript[$key]);
    }
  }
}

/**
 * Implements hook_page_alter().
 */
function plus_page_alter(&$page) {
  $element = [
    '#type' => 'html_tag',
    '#tag' => 'script',
    '#attributes' => [
      'type' => 'application/json',
      'data-drupal-selector' => 'drupal-settings-json',
    ],
    '#pre_render' => ['plus_render_drupal_settings'],
    '#value' => '{}',
  ];
  drupal_add_html_head($element, 'drupalSettings');

  // Implement a custom #attached callback to the page. This is necessary in
  // case any hook_page_build() or hook_page_alter() implementations attach
  // additional assets/libraries to the render array. It's also the last step
  // in the rendering process of drupal_render().
  $page['page_bottom']['plus']['#attached']['_plus_page_alter'] = [[]];
}

function plus_get_loaded_libraries() {
  // Backport of D8's indication of which libraries have been loaded.
  $libraries = [];
  foreach (drupal_static('drupal_add_library', []) as $module => $library) {
    foreach (array_keys(array_filter($library)) as $name) {
      $libraries[] = "$module/$name";
    }
  }
  natsort($libraries);
  return array_values($libraries);
}

/**
 * Callback for attaching ES6 files as a Drupal setting to the page.
 */
function _plus_page_alter() {
  $settings = [];

  $options = ['absolute' => TRUE];

  // Create a list of common files to load.
  $module_path = drupal_get_path('module', 'plus');
  $settings['loaderFiles'] = [
    // Drupal.Loader.es6.js must always be first!
    url($module_path . '/js/Drupal.Loader.es6.js', $options),
    url($module_path . '/js/Drupal.AsyncQueue.es6.js', $options),
    url($module_path . '/js/Drupal.Url.es6.js', $options),
    url($module_path . '/js/Drupal.Asset.es6.js', $options),
    url($module_path . '/js/Drupal.AssetManager.es6.js', $options),
    url($module_path . '/js/Drupal.Storage.es6.js', $options),
  ];

  // Backport of D8's indication of which libraries have been loaded.
  $settings['ajaxPageState']['library'] = plus_get_loaded_libraries();

  // Immediately return if ES6 support has been disabled.
  if ($es6 = drupal_static('plus_js_alter:es6', [])) {
    // Indicate that this is in the rendering phase so the alter doesn't recurse.
    $es6['es6_rendering'] = TRUE;

    // Generate the output for the files (absolute paths + aggregation).
    preg_match_all('/src="([^"]+)"/', drupal_get_js('header', $es6), $es6_matches);

    // Merge in any additional es6 files that should be loaded.
    $settings['loaderFiles'] = array_unique(array_merge($settings['loaderFiles'], $es6_matches[1]));
  }

  // Add the settings data.
  drupal_add_js($settings, 'setting');
}

function plus_pre_render_scripts(array $elements) {
  foreach ($elements['#items'] as $key => $info) {
    if (isset($info['type']) && $info['type'] === 'drupalSettings') {
      $elements[] = [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => [
          'type' => 'application/json',
          'data-drupal-selector' => 'drupal-settings-json',
        ],
        '#context' => [
          'drupalSettings' => TRUE,
          'info' => $info,
        ],
        '#attached' => [
          'plus_render_drupal_settings' => [[]],
        ],
        '#value' => '{}',
      ];
      unset($elements['#items'][$key]);
    }
  }
  return $elements;
}

function plus_render_drupal_settings(array $element) {
  global $theme;
  global $language;

  drupal_get_js();

  // Backport similar JS cache id from Drupal 8, but just for drupalSettings.
  $libraries_to_load = plus_get_loaded_libraries();
  $runtime_settings = drupal_array_merge_deep_array(drupal_static('plus_js_alter:settings', []));
  $cid = 'plus:js:drupalSettings:' . $theme . ':' . $language->language . ':' . drupal_hash_base64(serialize($libraries_to_load));
  if (($cache = cache_get($cid)) && isset($cache->data) && is_array($cache->data)) {
    $settings = $cache->data;
  }
  else {
    // Pass the current runtime settings to the hooks, but only cache the
    // changes that were made to it as the entire runtime settings are likely
    // to change per page due to the nature of asset management in Drupal 7.
    $build_settings = $runtime_settings;
    foreach (module_implements('js_settings_build') as $module) {
      $function = $module . '_' . 'js_settings_build';
      $function($build_settings);
    }
    $settings = drupal_array_diff_assoc_recursive($build_settings, $runtime_settings);
    cache_set($cid, $settings);
  }

  // Merge built/cached settings with runtime settings.
  $settings = drupal_array_merge_deep($settings, $runtime_settings);

  // Allow modules and themes to alter the JS settings.
  drupal_alter('js_settings', $settings);

  if ($settings) {
    $element['#context']['drupalSettings'] = $settings;
    $element['#value'] = function_exists('advagg_json_encode') ? advagg_json_encode($settings) : drupal_json_encode($settings);

    // Gzip and base64 encode the settings.
    if (variable_get('plus_drupal_settings_gzip', TRUE) && variable_get('js_gzip_compression', TRUE) && extension_loaded('zlib')) {
      $element['#attributes']['data-drupal-gzip'] = 'true';
      $element['#value'] = base64_encode(gzencode($element['#value'], 9));
    }
  }

  return $element;
}

/**
 * Implements hook_theme().
 */
function plus_theme($existing, $type, $theme, $path) {
  return [
    'file_link__plus' => [
      'base hook' => 'file_link',
      'variables' => ['file' => NULL, 'icon_directory' => NULL, 'icon' => TRUE],
    ],
    'no_file_icon' => [],
  ];
}

/**
 * Do not show file icons, which can take up valuable space.
 */
function theme_no_file_icon() {
  return '';
}

/**
 * Override theme hook.
 */
function plus_preprocess_file_link(&$variables) {
  $variables['theme_hook_suggestions'][] = 'file_link__plus';
}

/**
 * Override file_link theme hook.
 */
function theme_file_link__plus($variables) {
  $file = $variables['file'];

  $url = file_create_url($file->uri);

  // Show icon.
  $icon = '';
  if ($variables['icon']) {
    // Human-readable names, for use as text-alternatives to icons.
    $mime_name = array(
      'application/msword' => t('Microsoft Office document icon'),
      'application/vnd.ms-excel' => t('Office spreadsheet icon'),
      'application/vnd.ms-powerpoint' => t('Office presentation icon'),
      'application/pdf' => t('PDF icon'),
      'video/quicktime' => t('Movie icon'),
      'audio/mpeg' => t('Audio icon'),
      'audio/wav' => t('Audio icon'),
      'image/jpeg' => t('Image icon'),
      'image/png' => t('Image icon'),
      'image/gif' => t('Image icon'),
      'application/zip' => t('Package icon'),
      'text/html' => t('HTML icon'),
      'text/plain' => t('Plain text icon'),
      'application/octet-stream' => t('Binary Data'),
    );

    $mimetype = file_get_mimetype($file->uri);

    $icon = theme('file_icon', array(
      'file' => $file,
      'icon_directory' => $variables['icon_directory'],
      'alt' => !empty($mime_name[$mimetype]) ? $mime_name[$mimetype] : t('File'),
    ));
  }

  if (!isset($file->extension)) {
    $info = pathinfo($file->filename);
    $file->extension = $info['extension'];
  }

  // Set options as per anchor format described at
  // http://microformats.org/wiki/file-format-examples
  $options = array(
    'attributes' => array(
      'data-basename' => basename($file->filename, '.' . $file->extension),
      'data-extension' => $file->extension,
      'data-fid' => $file->fid,
      'data-filename' => $file->filename,
      'data-filesize' => $file->filesize,
      'data-uid' => $file->uid,
      'type' => $file->filemime . '; length=' . $file->filesize,
    ),
  );

  // Use the description as the link text if available.
  if (empty($file->description)) {
    $link_text = $file->filename;
  }
  else {
    $link_text = $file->description;
    $options['attributes']['title'] = check_plain($file->filename);
  }

  return '<span class="file">' . $icon . l($link_text, $url, $options) . '</span>';
}
