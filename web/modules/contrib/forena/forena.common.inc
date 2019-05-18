<?php
// $Id$
/**
 * @file
 * Common functions used throughout the project but loaded in this
 * file to keep the module file lean.
 */
use Drupal\forena\FrxAPI;

/**
 * Build cache
 *
 * @param \SimpleXMLElement|string $r_xhtml Forena XML report.
 * @return array data to be stored in the cache field for the report in the database.
 */
function forena_load_cache($r_xhtml) {
  $conf=array();
  $cache = array();
  $blocks = array();
  $repos = array();
  $nodes = array();
  if (is_object($r_xhtml)) {
    $head = @$r_xhtml->head;

    if ($head)  $nodes = $head->xpath('frx:menu');
    $menu = array();
    if ($nodes) {
      $m = $nodes[0];
      $attrs = $m->attributes();
      foreach ($attrs as $key => $value) {
        $menu[$key] = (string)$value;
      }
    }
    if ($menu) $cache['menu'] = $menu;

    $block_xml = $r_xhtml->xpath('//*[@frx:block]');
    // Extract all the blocks and organize by provider

    foreach ($block_xml as $block_node) {
      $attrs = $block_node->attributes('urn:FrxReports');
      foreach ($attrs as $key => $value) {
        if ($key == 'block') {
          @list($provider, $block) = explode('/', $value, 2);
          $repos[$provider][] = $block;
        }
      }
    }

    if ($repos) foreach ($repos as $provider_key => $blocks) {
      $provider = FrxAPI::RepoMan()->repository($provider_key);
      if (isset($provider->conf))$conf = $provider->conf;
      $access = array();
      foreach ($blocks as $block_name) {
        if ($provider && $block_name) {
          if (method_exists($provider, 'loadBlock')) {
            $conf = $provider->conf;
            $block = $provider->loadBlock($block_name);
            $obj = @$block['access'];
            if (array_search($obj, $access)===FALSE) $access[]=$obj;
          }
        }
      }

      if (isset($conf['access callback']) && $access) $cache['access'][$provider_key][$conf['access callback']]=$access;


    }



  }
  return $cache;
}

/**
 * Object factory for forena report
 * When called without a report name, it returns the last created report.
 * This static caching mechanism is used for form functions that are called
 * within a page load.
 *
 * @param string $report 
 *   Name of report. 
 * @param array $data 
 *   Data for report
 * @return \Drupal\forena\FrxPlugin\Context\FrxReport
 */
function forena_report_object($report='', $data=array()) {
  static $object = '';
  if ($report) {
    $object = new FrxReport($report, $data);
  }
  return $object;
}


/**
 * Accepts the name of the html tag, and the string the tag is in.
 *
 * Returns the string within the html tag name
 * 
 * @param string $tag
 *   Name of tag
 * @param string $r_text
 *   XML containing full tag. 
 *
 */
function forena_get_html($tag, $r_text) {
  $open = strpos($r_text, $tag);
  $close = strpos($r_text, '>', $open);
  $next = strpos($r_text, '<', $close + 1);
  $str = substr($r_text, $close + 1, $next - ($close + 1));

  return $str;
}

/**
 * Get 
 * @param array $category
 *   Array of categories to include
 * @return array
 */
function forena_get_user_reports($category = array()) {
  if (!$category) $category = array();
  $reports = \Drupal\forena\File\ReportFileSystem::instance()->reportsByCategory((array)$category);
  return $reports;
}

/**
 * Render the my reports category block
 *
 */
function forena_my_reports_block() {
  $reports = forena_get_user_reports();
  if (!$reports) return '';
  $output = '<ul>';
  foreach ($reports as $category => $report) {
    // @FIXME Provide a valid URL, generated from a route name, as the second argument to l(). See https://www.drupal.org/node/2346779 for more information.
// $output .= '<li>' . l($category, 'forena', array('fragment' => urlencode($category))) . '</li>';

  }
  $output .= '</ul>';
  return $output;
}

/**
 * Email confirmation form.   Confirms an email send based on mail merge
 * @param array $form 
 *   Form definition start
 * @param array $form_state
 *   Form state array 
 * @param array $docs 
 *   An array of SimpleXML email documents to send
 * @param integer $count 
 *   Number of documents to send.
 * @param string $prompt_subject
 *   Subject for the prompt
 * @param string $prompt_body
 *   The body of the email entered. 
 */
function forena_confirm_email($form, &$form_state, $docs, $count, $prompt_subject, $prompt_body) {
  // Make sure user has permission for email merge.
  if (!\Drupal::currentUser()->hasPermission('perform email merge')) {
    drupal_access_denied();
    exit();
  }
  // Save arguments away for rebuild
  if (!isset($form_state['storage']['args'])) {
    $form_state['storage']['args'] = array(
      'docs' => $docs,
      'count' => $count,
      'prompt_subject' => $prompt_subject,
      'prompt_body' => $prompt_body,
    );
  }
  else {
    // Retrieve arguements for rebuild case
    extract($form_state['storage']['args']);
  }
  if ($docs) {
    $values = @$form_state['values'];
    if ($prompt_subject) {
      $form['subject'] = array(
        '#type' => 'textfield',
        '#title' => t('Subject'),
        '#default_value' => @$values['subject'],

      );
    }

    if ($prompt_body) {
      $form['body'] = array(
        '#type' => 'text_format',
        '#title' => t('Message'),
        '#default_value' => @$values['body'],
        '#format' => \Drupal::config('forena.settings')->get('forena_email_input_format')

      );
    }


    if (!\Drupal::config('forena.settings')->get('forena_email_override')) {
      $form['send'] = array(
        '#type' => 'radios',
        '#title' => t('Send Email'),
        '#options' => array('send' => 'email to users',
          'test' => 'emails to me (test mode)'),
        '#default_value' => 'test',
      );
    }
    $form['max'] = array(
      '#type' => 'textfield',
      '#title' => 'Only send first',
      '#description' => 'In test mode only, limits the number of messages to send',
      '#default_value' => 1,
      '#size' => 3,
    );

    $form_state['storage']['docs'] = $docs;
    $form_state['storage']['count'] = $count;
  }
  return confirm_form($form, t('Send mail to users'), 'forena', t('Send email to %count users?', array('%count' => $count)));
}

function forena_confirm_email_submit($form, &$form_state) {
  $user = \Drupal::currentUser();
  $test_send = @$form_state['values']['send']=='test' ? TRUE : \Drupal::config('forena.settings')->get('forena_email_override');
  $max = (integer)$form_state['values']['max'];
  $i = 0;
  if (isset($form_state['values']['body']['value'])) {
    $body =  check_markup($form_state['values']['body']['value'],$form_state['values']['body']['format']);
  }
  foreach ($form_state['storage']['docs'] as $doc) {
    $to = $test_send ? $user->mail : $doc['to'];
    $from = $doc['from'];
    // Replace body
    if (isset($form_state['values']['body'])) {
      $doc['parms']['body'] = $body;
    }
    // Replace subject
    if (isset($form_state['values']['subject'])) {
      $doc['parms']['subject'] = $form_state['values']['subject'];
    }
    if ($test_send) {
      $i++;
      // Remove bcc and cc
      unset($doc['parms']['headers']);
    }
    if ($i <= $max) drupal_mail('forena', 'mailmerge', $to, \Drupal::languageManager()->getLanguages(), $doc['parms'], $from, TRUE);
  }
  $form_state['rebuild'] = TRUE;
}

/**
 * Send report block XML
 * Enter description here ...
 * @param string $block_name
 *   Name of data block. 
 */
function forena_block_xml($block_name='') {
  $block_name = str_replace('.', '/', $block_name);
  $parms = $_GET;
  unset($parms['q']);
  $xml = FrxAPI::RepoMan()->data($block_name, $parms);
  if (is_object($xml)) {
    header('Content-Type: text/xml');
    print $xml->asXML();
  }
}

/**
 * Save the report file to disk
 *
 * @param string $report_name 
 *   File name  to save report to
 * @param string $report  
 *   Report xml to save. 
 * @param $save_file
 *   Wheter to save file to disk. 
 * @param $altered
 *   Where or not he report has been altered? 
 * @return int
 *   Indictates whether report was successfully saved. 
 *   
 */
function forena_save_report($report_name, $report, $save_file = FALSE, $altered = 0) {
  static $save_count=0;
  if ($report && !is_object($report)) {
    try {
      $report = new SimpleXMLElement($report);
    }
    catch (Exception $e) {
      $this->error(t('Could not save %s because XML was malformed', htmlspecialchars($report_name)),
        "<p>Invalid XML</p><pre>XML:" . htmlspecialchars($report) . "\n" . $e->getMessage() . "</pre>");
      return 0; 
    }
  }

  if (!_forena_verify_directory($report_name) && $save_file) {
    drupal_set_message(t('Error creating directory.  Check Permissions'), 'error');
    return 0;
  };
  $report_path = forena_report_path();
  $r = new FrxReport($report);
  $data['title'] = $r->title;
  $data['category'] = $r->category;
  $data['options'] = $r->options;
  $data['descriptor'] = $r->descriptor;
  $data['name'] = $report_name;
  $data['altered'] = $altered;
  $r_xml = $report->asXML();
  $name = $data['name'];
  $filepath = $report_name . '.frx';
  // If we need to save this to the file system

  if ($save_file) {
    FrxAPI::File()->save($filepath, $r_xml);
  }

  // Get the security caches from the reports
  if ($report) $cache = forena_load_cache($report); else $cache='';
  $rpt_cache='';
  if ($cache) $rpt_cache = serialize($cache);

  // Set default interpretations of data
  $data['enabled'] = (isset($data['enabled']) && $data['enabled']) ? 1 : 0;
  if (isset($data['options']['hidden']) && (string)$data['options']['hidden']) {
    $data['hidden'] =  ($data['options']['hidden'] && $data['options']['hidden']!='N' && $data['options']['hidden']!='0') ? 1:0;
    if (!$data['category']) $data['category'] = 'All';
  }
  else {
    // Set hidden based on category
    $data['hidden'] = ($data['category'])? 0 : 1;
  }

  $save_count++;
  $r = NULL;
  $result = NULL;
  $r_xml = NULL;
  $report = NULL;
  $data = NULL;
  $rpt = NULL;
  $cache = NULL;
  $rpt_cache = NULL;
  // Destroy object to clear memory leaks.
  if ($r) {
    $r->__destruct();
    unset($r);
  }

  return $save_count;
}

/**
 * Render a field without it's containers so that we can use it in a report without the wrappers.
 */
function theme_forena_inline_field(&$variables) {
  $element = $variables['field'];
  // This is also used in the installer, pre-database setup.
  drupal_render_children($variables['field']);
}

