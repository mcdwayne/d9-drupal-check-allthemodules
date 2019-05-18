<?php
// $Id$
/**
 * @file
 * Report administration forms and functions.
 */
require_once('forena.common.inc');

/**
 * Display reports to edit for admins in the structure menu
 * Enter description here ...
 */
function forena_admin_reports() {
  GLOBAL $language;
  $data = array();
  $output = '';
  $content = drupal_get_form('forena_sync_form');
  $sync_form = drupal_render($content);
  $links[] = array('href' => 'reports/add', 'title' => 'Create New Report' );
  $output .= _theme('links',
    array(
      'links' => $links,
      'attributes' => array('class' => 'action-links'),
    )
  );
  // Add Data tables if it exists.
  $headers = array(t('category'), t('title'), t('name'), t('operation'));
  $result = Frx::File()->allReports();
  foreach ($result as $row) {
    $rpt = str_replace('/', '.', $row->name);
    if ($row->include) {
      // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $edit = l(t('Override'), 'reports/' . $rpt . '/edit');

    }
    else {
      // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $edit = l(t('Edit'), 'reports/' . $rpt . '/edit');

    }
    // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $clone = l(t('Clone'), 'reports/add/' . $rpt);

    // Determine the nature of the report delete link.
    if ($row->override) {
      // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $delete = l(t('Revert'), 'reports/' . $rpt . '/delete', array('query' => array('destination' => 'admin/structure/forena')));

    }
    else if (!$row->include) {
      // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $delete = l(t('Delete'), 'reports/' . $rpt . '/delete', array('query' => array('destination' => 'admin/structure/forena')));

    }
    else {
      $delete = '';
    }

    // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $title = l(t($row->cache['title']), 'reports/' . $rpt);

    $data[] = array($row->cache['category'], $title, $row->name, $edit . ' ' . $clone . ' ' . $delete);
  }
  $output .= '<div id="forena-reports-list">';
  $output .= _theme('forena_data_table', array('header' => $headers, 'rows' => $data));
  $output .= '</div>';
  $output .= $sync_form;
  return $output;
}






function forena_filter_element($fmt, $name) {
  $user = \Drupal::currentUser();
  $element['format'] =  array(
    '#type' => 'fieldset',
    '#collapsible' => TRUE,
    '#collapsed' => $fmt!='',
    '#title' => t('Input formats'),
  );
  if (!$fmt) $fmt='full_html';
  // Get a list of formats that the current user has access to.
  $formats = filter_formats($user);
  foreach ($formats as $format) {
    $options[$format->format] = $format->name;
    $element['format']['guidelines'][$format->format] = array(
      '#theme' => 'filter_guidelines',
      '#required' => TRUE,
      '#format' => $format,
    );
  }
  $element['format']['guidelines']['#weight']=12;

  $element['format'][$name] = array(
    '#type' => 'select',
    '#title' => t('Text format'),
    '#options' => $options,
    '#default_value' => $fmt,
    '#access' => count($formats) > 1,
    '#weight' => 10,
    '#attributes' => array('class' => array('filter-list')),
  );

  $element['format']['help'] = array(
    '#type' => 'container',
    '#theme' => 'filter_tips_more_info',
    '#attributes' => array('class' => array('filter-help')),
    '#weight' => 11,
  );
  return $element['format'];
}



/**
 * Report syncronization form
 * @param $formid
 * @param $form_state
 * @return array 
 *   Syncronization form . 
 */
function forena_sync_form($formid, &$form_state) {

  $form['sync_overwrite'] = array(
    '#type' => 'checkbox',
    '#title' => t('Revert all delivered reports to orignial'),
    '#required' => TRUE,
    '#description' => t('All customizations to module delivered reports will be lost.')
  );

  $form['sync'] = array(
    '#type' => 'submit',
    '#value' => t('Revert'),
    '#submit' => array('forena_settings_sync_submit'),
  );

  return $form;

}

/**
 * Forena admin settings form
 *
 */
function forena_settings() {
  $skins = Frx::File()->skins();

  $report_path = forena_report_path();


  $form['forena_report_repos'] = array(
    '#type' => 'textfield',
    '#title' => t('Report Repository'),
    '#description' => t('Indicate the directory that you want to use for your reports.  In order for you to ' .
                        'to be able to save reports, this directory should be writable by the web user. Relative' .
                        'paths should be entered relative to the base path of your drupal installation.'),
    '#default_value' => $report_path,
  );

  $form['forena_last_report_path'] = array(
    '#type' => 'value',
    '#value' => \Drupal::config('forena.settings')->get('forena_report_repos')
  );

  if (\Drupal::moduleHandler()->moduleExists('forena_query')) {
      $form['forena_query_data_path'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom Data Block Repository'),
        '#description' => t('Indicate the directory that you want to use to save custom created data blocks using
            the forena query builder tool.  These will override any module delivered data blocks.  This needs to
            be a directory that is writable by the web user but should not be browsable by the web.  It defaults to
            '),
        '#default_value' => Frx::DataFile()->dir,
      );

      $form['forena_last_query_data_path'] = array(
          '#type' => 'value',
          '#value' =>  \Drupal::config('forena.settings')->get('forena_query_data_path')
      );

  }

  $form['forena_input_format'] = forena_filter_element(\Drupal::config('forena.settings')->get('forena_input_format'), 'forena_input_format');
  $form['forena_default_form'] = array(
    '#type' => 'select',
    '#title' => t('Default report skin'),
    '#options' => $skins,
    '#description' => t('Specify the default skin to be used.   New skins can be created by creating .skin.yml files in your reports directory.'
    . ' Skins are basically css and javascript libraries added to your report.'),
    '#default_value' => \Drupal::config('forena.settings')->get('forena_default_form'),
    );

    $form =  system_settings_form($form);
    return $form;

}

function forena_settings_validate($form, &$form_state) {
  $values = $form_state['values'];
  $path = $values['forena_report_repos'];
  if ($path != $values['forena_last_report_path']) {
    if (!file_exists($path)) {
      try {
        if (file_exists($path)) {
          drupal_set_message(t('Created directory %s', array('%s' => $path))) ;
        }
        mkdir($path);

      } catch (Exception $e) {
        Frx::error(t('Unable to create report directory'), $e->getMessage());
      }
    }
  }
  if (!file_exists($path) || !is_writable($path)) {
    form_set_error('forena_report_repos', 'Report Directory must be writable by the web user');
  }
  if (isset($values['forena_query_data_path'])) {
    $path = $values['forena_query_data_path'];
    if ($path != $values['forena_last_query_data_path']) {
      if (!file_exists($path)) {
        try {

          mkdir($path);
          if (file_exists($path)) {
            drupal_set_message(t('Created directory %s', array('%s' => $path))) ;
          }

        } catch (Exception $e) {
          Frx::error(t('Unable to create data directory'), $e->getMessage());
        }
      }
    }
    if (!file_exists($path) || !is_writable($path)) {
      form_set_error('forena_query_data_path', 'Data Directory must be writable by the web user');
    }
  }


}


/**
 * Added submit handler to create directories and clear menu cache
 *
 * @param unknown_type $form
 * @param unknown_type $form_state
 */
function forena_settings_submit($form, &$form_state) {
}

function forena_settings_sync_submit($form, &$form_state) {
  forena_sync_reports($form_state['values']['sync_overwrite']);
}

/**
 * Form function for the edit report form
 * @param $form_state
 * @return the form
 */


/**
 * Handle delete buttons from edit forms.
 * @return unknown_type
 */
function forena_edit_delete_submit($form, &$form_state) {
  $link = 'reports/' . $form_state['values']['name_in'] ;
  $destination = '';
  if (isset($_REQUEST['destination'])) {
    $destination = drupal_get_destination();
    unset($_REQUEST['destination']);
  }
  $form_state['redirect'] = array('path' => $link . '/delete', 'query' => array('destination' => $destination));
}


function forena_create_trans_form($formid, $form_state, $report_name) {
  $name = '';
  $filename = '';
  $format = '';
  $desc = Frx::Menu()->parseURL($report_name);


  $form = array();
  $language = \Drupal::languageManager()->getCurrentLanguage();
  $languages = locale_translatable_language_list();
  //determine if this is an add new report request

  $r = Frx::Editor($report_name, FALSE);
  $base_name = $r->desc['base_name'];
  $title = (string)$r->title;
  $lang = @$_GET['target_language'];
  if ($lang) {
    $language = $languages[$lang];
  }
  $form['base_name'] = array(
    '#type' => 'value',
    '#value' => $base_name
  );
  $form['report_name'] = array(
    '#type' => 'value',
    '#value' => $name,
  );

  foreach ($languages as $key => $obj) {
    $languages[$key] = $obj->native;
  }
  $form['report_lang'] = array(
    '#type' => 'value',
    '#value' => $lang,
  );
  $def_lang = $lang ? $lang : 'en';
  $form['save_report_language'] = array(
    '#type' => 'select',
    '#title' => t('Language'),
    '#options' => $languages,
    '#default_value' => $def_lang,
  );


  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $title,
    '#required' => TRUE,
  );


  $form['save'] = array(
    '#type' => 'submit',
    '#value' => 'Create',
  );
  return $form;

}

function forena_create_trans_form_submit($form, &$form_state) {
  $language = \Drupal::languageManager()->getCurrentLanguage();
  $language = \Drupal::languageManager()->getLanguages();
  $values = $form_state['values'];
  $base_name = $values['base_name'];
  $new_name = $values['save_report_language'] . '/' . $base_name;
  $r = Frx::Editor($base_name, TRUE);
  $link = $r->report_link;

  // Determine new fielname
  $desc = Frx::Menu()->parseURL($new_name);
  $filename = $desc['filename'];
  $report_name = $desc['name'];

  //determine redirection.

  if (Frx::File()->exists($filename, FALSE)) {
    drupal_set_message(t('Report %s already exists', array('%s' => $new_name)), 'error');
    return NULL; 
  }
  else {
    // Title and category
    $r->setTitle($values['title']);
    $r->update();
    $r->rename($new_name);
    drupal_set_message(t('Translation,  %s has been created. Switch languages to translate.', array('%s' => $values['title'])));
    //$r->cancel();
    $r->save();
    $form_state['redirect']= $link . '/edit';
  }

  //if this is a new report then redirect to data blocks
}



/*
 * administer the settings for document format options
 */
function forena_doc_formats_settings() {
  // Invoke doc_type hook to see which document types are there.
  $supported_doctypes = Frx::documentTypes();

  $form['forena_doc_formats'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Allowed Document Formats'),
    '#default_value' => \Drupal::config('forena.settings')->get('forena_doc_formats'),
    '#description' => t('check your desired document format'),
    '#options' => $supported_doctypes,
  );

  $form['forena_doc_defaults'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Default Document Formats'),
    '#default_value' => \Drupal::config('forena.settings')->get('forena_doc_defaults'),
    '#description' => t('check your desired document format'),
    '#options' => $supported_doctypes,
  );

  $form['forena_email_override'] = array(
    '#type' => 'checkbox',
    '#title' => 'Run email merges in test mode' ,
    '#default_value' => \Drupal::config('forena.settings')->get('forena_email_override'),
    '#description' => t('When this box is checked emails are sent to the currently logged in user.  Useful for testing environments.'),
  );
  $form['forena_email_input_format'] = forena_filter_element(\Drupal::config('forena.settings')->get('forena_email_input_format'), 'forena_email_input_format');
  $form['forena_email_input_format']['#title'] = t('Email Input Format');

  return system_settings_form($form);
}

/*
 * administer the settings for document format options
 */
function forena_data_settings() {
  $repos = Frx::RepoMan()->repositories;
  $r_list = array();
  $headers = array(t('Name'), t('Description'), t('Path'), t('Operation'));
  foreach ($repos as $name => $r) {
    // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $r_list[] = array(
//     $name,
//     $r['title'],
//     $r['path'],
//     l(t('configure'), 'admin/config/content/forena/data/configure/' . $name)
//     );


  }

  // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $output = '<ul class="action-links"><li>' . l(t('Add data source'), 'admin/config/content/forena/data/add') . '</li></ul>';

  $output .= theme_table(array('header' => $headers, 'rows' => $r_list, 'attributes' => array(), 'caption' => '', 'sticky' => TRUE, 'colgroups' => array(), 'empty' => ''));
  return $output;
}

function forena_data_settings_edit($form, &$form_state, $source=-1) {

}



/**
 * Submit handler to cause save events to happen.
 * Enter description here ...
 */
function forena_data_settings_save($form, &$form_state) {
  $values = $form_state['values'];
  $name = $values['name'];
  $config = $form_state['storage']['config'];
  $config['source'] = @$values['source'];
  $config['driverr'] = $values['driver'];
  $config['user callback'] = $values['user_callback'];
  $config['debug'] = $values['debug'];
  if (@$values['connection']['new_password']) {
    $values['connection']['password'] = $values['connection']['new_password'];
  }
  if (isset($values['connection']['new_password'])) unset($values['connection']['new_password']);
  if (is_array(@$values['connection'])) $config = array_merge($config, @$values['connection']);
  if ($values['access_method']=='callback') {
    $config['access callback'] = empty($values['access_callback']) ? 'forena_user_access_check' : $values['access_callback'];
    if (isset($config['access block'])) unset($config['access block']);
  }
  else {
    $config['access block'] = $values['access_block'];
  }
  $config_str = serialize($config);
  $result = db_query('SELECT * FROM {forena_repositories} WHERE repository = :name', array(':name' => $name));

  if ($result) drupal_set_message(t('Data connection settings saved'));
  if ($repos = $result->fetchObject()) {
    db_update('forena_repositories')
    ->fields(array(
        'title' => $values['title'],
        'enabled' => $values['enabled'],
        'config' => $config_str,
    ))
    ->condition('repository', $name, '=')
    ->execute();
  }
  else {
    $form_state['#redirect'] = 'admin/forena/content/data';
    db_insert('forena_repositories')
    ->fields(array(
        'repository' => $name,
        'title' => $values['title'],
        'enabled' => $values['enabled'],
        'config' => $config_str,
    ))
    ->execute();
  }
  $form_state['redirect'] = 'admin/config/content/forena/data';
}

/**
 * Delete the function
 * Enter description here ...
 * @param unknown_type $form
 * @param unknown_type $form_state
 */
function forena_data_settings_delete($form, &$form_state) {
  if (!@$form_state['storage]']['locked']) {
    $form_state['redirect'] = 'admin/config/content/forena/data';
    db_delete('forena_repositories')
    ->condition('repository', $form_state['values']['name'], '=')
    ->execute();
  }

}

/**
 * Generates the controls for external databases.
 * Enter description here ...
 */
function forena_connection_info_callback($form, $form_state) {
  return $form['connection'];

}

function forena_access_info_callback($form, $form_state) {
  return $form['access_details'];
}



/**
 * Clean xhtml
 *
 * @param unknown_type $xhtml
 * @return unknown
 */
function forena_clean_xhtml($xhtml) {
  $ret = $xhtml;
  // If tidy is installed lets clean the html using that.
  if (is_callable('tidy_repair_string')) {
    $config = array(
      'doctype'            => 'omit',
      'indent-spaces'             => 2,
      'indent' => 'auto',
      'input-xml'          => TRUE,
      'output-xml'         => TRUE,
      'indent-attributes'  => FALSE,
      'add-xml-space'      => TRUE,
      'wrap'               => 135 );
    $ret = tidy_repair_string($xhtml, $config, 'utf8');
  }
  else {
    $ret = str_replace('&nbsp;', '&#160;', $ret);
  }
  return $ret;
}

/**
 * Prettifies an XML string into a human-readable and indented work of art
 * @param string $xml The XML as a string
 * @param boolean $html_output 
 *   True if the output should be escaped (for use in HTML)
 * @return string 
 *   Formatted string represnetation of XML. 
 */
function forena_xmlpp($xml, $html_output=FALSE) {
  $xml_obj = new SimpleXMLElement($xml);
  $level = 4;
  $indent = 0; // current indentation level
  $pretty = array();

  // get an array containing each XML element
  $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml_obj->asXML()));

  // shift off opening XML tag if present
  if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
    $pretty[] = array_shift($xml);
  }

  foreach ($xml as $el) {
    if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
      // opening tag, increase indent
      $pretty[] = str_repeat(' ', $indent) . $el;
      $indent += $level;
    }
    else {
      if (preg_match('/^<\/.+>$/', $el)) {
        $indent -= $level;  // closing tag, decrease indent
      }
      if ($indent < 0) {
        $indent += $level;
      }
      $pretty[] = str_repeat(' ', $indent) . $el;
    }
  }
  $xml = implode("\n", $pretty);
  return ($html_output) ? htmlentities($xml) : $xml;
}

/**
 * Auto complete function for categories
 * Checks access for security as well.
 *
 * @param $string = string to be matched against categories
 * @return An array containing all matching categories
 */
function forena_get_categories($string='') {
  $data = Frx::File()->reportsByCategory();
  if ($string) {
    $categories = array();
    foreach (array_keys($data) as $cat) {
      if (strpos(strtolower($cat), strtolower(trim($string))) === 0) {
        $categories[] = $cat;
      }
    }

  }
  else {
    $categories = array_keys($data);
  }
  $categories = array_combine($categories, $categories);
  return $categories;
}

function forena_template_ajax(&$form_element) {
  $form_element['#ajax'] = array(
        'callback' => 'forena_template_info_callback',
        'wrapper' => 'template-wrapper',
  );
}

function forena_query_string($var) {
  return drupal_http_build_query($var);
}


