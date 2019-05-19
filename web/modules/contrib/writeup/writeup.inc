<?php
/**
 * @file
 * Include file for Writup Filter module containing functions that are common between different versions of Drupal.
 *
 */

/**
 * Common Writeup Processing
 *
 * @param $tmpfile
 *   Name of temporary file in which unprocessed text was saved
 *
 * @param $formatname
 *   Full name of format to pass to Writeup in $format variable
 *
 * @param $format_incname
 *   Include file specified for this input format
 *
 * @param $format_settings
 *   Any special settings for this input format
 *
 * @return
 *   string containing the processed text
 */
function _writeup_process($tmpfile, $formatname, $format_incname, $format_settings) {
  $incfile = rtrim(\Drupal::config('writeup.settings')->get('writeup_incdir'), '/\\') . '/' . $format_incname;
  if (!is_file($incfile)) $incfile='NONE';
  $settings = "-s include=\"$incfile\" -s format=\"$formatname\" " . escapeshellcmd($format_settings);
  $ver = \Drupal::config('writeup.settings')->get('writeup_ver');
  if ($ver != '') $settings .= " -s VER=$ver";
  $writeup = rtrim(\Drupal::config('writeup.settings')->get('writeup_loc'), '/\\') . '/writeup';
  $command = "$writeup -Iyqbrc $settings $tmpfile";
  $output = array();
  $retvar = 0;
  exec($command, $output, $retvar);
  $errorblock = "";
  if ($retvar != 0) {
    $errors = array();
    _writeup_get_errors($tmpfile, $formatname, $format_incname, $format_settings, $errors);
    if ($retvar == 1) {
      $severity =  WATCHDOG_WARNING;
      $warnings = t('There are warnings in your content:');
    }
    else {
      $severity =  WATCHDOG_ERROR;
      $warnings = t('There are errors in your content:');
    }
    $errormsg = '<pre>' . $errors . '</pre>';
    if (\Drupal::config('writeup.settings')->get('writeup_logerrors')) \Drupal::logger('writeup')->notice($errormsg, []);
    if (\Drupal::config('writeup.settings')->get('writeup_showerrors')) $errorblock = '<div class="messages error writeuperror">' . $errormsg . '</div>';
    drupal_set_message($warnings . $errormsg, 'error');
  }
  return $errorblock . implode("\n", $output);
}

/**
 * Test Writeup document and get any error messages
 *
 * @param $tmpfile
 *   Name of temporary file in which unprocessed text was saved
 *
 * @param $formatname
 *   Full name of format to pass to Writeup in $format variable
 *
 * @param $format_incname
 *   Include file specified for this input format
 *
 * @param $format_settings
 *   Any special settings for this input format
 *
 * @param $errors
 *   String containing any error messages
 *
 * @return
 *   Return code from Writeup (0=good)
 */
function _writeup_get_errors($tmpfile, $formatname, $format_incname, $format_settings, &$errors='') {
  $incfile = rtrim(\Drupal::config('writeup.settings')->get('writeup_incdir'), '/\\') . '/' . $format_incname;
  if (!is_file($incfile)) $incfile='NONE';
  $settings = "-s include=\"$incfile\" -s format=\"$formatname\" " . escapeshellcmd($format_settings);
  $ver = \Drupal::config('writeup.settings')->get('writeup_ver');
  if ($ver != '') $settings .= " -s VER=$ver";
  $writeup = rtrim(\Drupal::config('writeup.settings')->get('writeup_loc'), '/\\') . '/writeup';
  $command = "$writeup -Iyqbc -X NULL $settings $tmpfile 2>&1";
  $output = array();
  $retvar = 0;
  exec($command, $output, $retvar);
  $errors = \Drupal\Component\Utility\Xss::filterAdmin(implode("\n", $output)); // sanitize before display
  return $retvar;
}

/********************** Admin Functions **************************/
/**
 * Implements hook_help().
 * Display help and module information
 *
 * @param path
 *   which path of the site we're displaying help
 *
 * @param arg
 *   array that holds the current path as would be returned from arg() function
 *
 * @return
 *   help text for the path
 */
use Drupal\Core\Routing\RouteMatchInterface;

function writeup_help($route_name, RouteMatchInterface $route_match) {
  $output = ''; // declare output variable
  switch ($route_name) {
//    case 'admin/config/content/formats/manage/':
    case 'help.page.writeup':
//function writeup_help($path = 'admin/help#writeup', $arg) {
//  switch ($path) {
//    case "admin/help#writeup":
      $output = '<p>' . t('Input filter that processes text using the <a href="http://writeup.org">Writeup</a> markup language. See <a href="http://writeup.org">writeup.org</a> for details.')
         . '</p><p>' . t('In order for the filter to work, the Writeup executable binary must be installed on the server.')
         . '</p><p>' . t('The linux binary may be downloaded here: https://writeup.googlecode.com/svn/bin/writeup')
         . '</p><p>' . t('or from http://sourceforge.net/projects/writeup/files') . '</p>';
      break;
  }
  return $output;
}

/**
 * Checks the existence of the file specified in $form_element.
 * If validation fails, the form element is flagged.
 *
 * @param $form_element
 *   The form element containing the name of the file to check.
function writeup_check_file($form_element) {
  $directory = rtrim(\Drupal::config('writeup.settings')->get('writeup_incdir'), '/\\');
  if (!is_dir($directory)) {   // Check if directory exists.
    form_set_error($form_element['#parents'][0], t('The Writeup directory %directory set on the administration page does not exist.', array('%directory' => $directory)));
  }
  else {
    $path = $directory . '/' . $form_element['#value'];
    if (!is_file($path)) {   // Check if file exists.
      form_set_error($form_element['#parents'][0], t('The file %path does not exist.', array('%path' => $path)));
    }
  }
  return $form_element;
}
 */

/**
 * Applies a very permissive XSS/HTML filter for admin-only use.
 * Modified version of Drupal 8 core function Xss::filterAdmin (which is missing the tags at the end)
 */
function writeup_filter_xss_admin($string) {
  // over twice as fast if this is static since array does not have to be built on every call
  static $allowed_tags = array('a', 'abbr', 'acronym', 'address', 'article', 'aside', 'b', 'bdi', 'bdo', 'big', 'blockquote', 'br', 'caption', 'cite', 'code', 'col', 'colgroup', 'command', 'dd', 'del', 'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure', 'footer', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'mark', 'menu', 'meter', 'nav', 'ol', 'output', 'p', 'pre', 'progress', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'section', 'small', 'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'time', 'tr', 'tt', 'u', 'ul', 'var', 'wbr', '!--',
  'audio', 'canvas', 'embed', 'fieldset', 'legend', 'path', 'rect', 'source', 'svg', 'track','video' // new tags added
  );
  // 'object' tag not added because of the <object data="javascript:..."> attack
  return \Drupal\Component\Utility\Xss::filter($string, $allowed_tags);
}
