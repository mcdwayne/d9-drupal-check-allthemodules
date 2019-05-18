<?php

/**
 * @file
 * Theme functions.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Page\HtmlPage;

/**
 * Process variables for securesite-user-login.html.twig
 *
 * @param $variables
 *   An array of variables from the theme system.
 */
function template_preprocess_securesite_login_form(&$variables) {
  $variables['title'] = String::checkPlain(\Drupal::config('securesite.settings')->get('securesite_login_form'));
  $variables['messages'] = _theme('status_messages');
  $variables['openid_identifier'] = drupal_render($variables['form']['openid_identifier']);
  $variables['name'] = $variables['form']['name'];
  $variables['form']['#attached'] = array('css' => array(drupal_get_path('module', 'securesite') . '/theme/securesite.css' => array()));
  $variables['pass'] = $variables['form']['pass'];
  $variables['submit'] = $variables['form']['submit'];
  $variables['openid_links'] = $variables['form']['openid_links'];
}

/**
 * Process variables for securesite-user-pass.html.twig
 *
 * @param $variables
 *   An array of variables from the theme system.
 */
function template_preprocess_securesite_user_pass(&$variables) {
  $variables['title'] = String::checkPlain(\Drupal::config('securesite.settings')->get('securesite_reset_form'));
  //$variables['form']['name']['#required'] = FALSE;
  $variables['name'] = $variables['form']['name'];
  $variables['submit'] = $variables['form']['submit'];
  //$variables['children'] = drupal_render_children($variables['form']);
}

/**
 * Process variables for securesite-page.html.twig
 *
 * @param $variables
 *   An array of variables from the theme system.
 */
function template_preprocess_securesite_page(&$variables) {
  // Add relevant default variables, taken from template_preprocess_page()
  $variables['messages']   = !empty($variables['show_messages']) ? _theme('status_messages') : '';
  $variables['base_path']  = base_path();
  $variables['logo']       = theme_get_setting('logo')['url'];
  $variables['front_page'] = url();

  // Check if logo exists before output it in a template file
  // First get relative path from logo theme setting
  $logo_parts = parse_url($variables['logo']);
  $logo_path = $logo_parts['path'];
  if (strpos($logo_path, $variables['base_path']) === 0) {
    $logo_path = substr($logo_path, strlen($variables['base_path']));
  }
  // If file does not exist clear it
  if (!file_exists($logo_path)) {
    $variables['logo'] = NULL;
  }
  
  // Clear out existing CSS
  $css = &drupal_static('_drupal_add_css', array());
  $css = array();
  
  // Add our css file back, so it will be the only one
  _drupal_add_css(drupal_get_path('module', 'securesite') . '/theme/securesite.css');
  // By default a lot unnecessary JS is load,
  // By default we should not need any JS.
  $js = &drupal_static('_drupal_add_js', array());
  $js = array();

  // Required by Core template_process_html()
  $variables['page_object'] = new HtmlPage();
  // Call Drupal core default html page preprocess function
  template_preprocess_html($variables);
}