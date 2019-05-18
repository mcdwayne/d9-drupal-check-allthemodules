<?php
/**
 * @file
 * Contains theme implementations for pathinfo.module.
 */

/**
 * Generates a link to Drupal API documentation.
 *
 * @param array $variables
 *   An associative array containing:
 *   - function: The name of the function to link to.
 *   - filepath: The full relative path to the file the function is in.
 *   - is_core: Boolean, TRUE when function is in Drupal core.
 *
 * @ingroup themeable
 */
function theme_pathinfo_api_link($variables) {
  if ($variables['is_core']) {
    // Drupal API functions have the file path in the url.
    $url = 'http://api.drupal.org/api/drupal/%s/function/%s/7';
    // Urls in API module are encoded in a funny way.
    $url_filepath = str_replace('/', '%21', $variables['filepath']);
    $url = sprintf($url, $url_filepath, $variables['function']);
  }
  else {
    $url = 'http://api.drupalize.me/api/drupal/function/%s/7';
    $url = sprintf($url, $variables['function']);
  }

  // No t() here because we don't want to change function names!
  return l(
    check_plain($variables['function']),
    filter_xss($url),
    array(
      'options' => array(
        'external' => TRUE,
      )
    )
  );
}

/**
 * Formats complex arguments.
 * 
 * @param array $variables
 *   An associative array containing:
 *   - link_text: Text to show as the link to view the arguments.
 *   - arguments: Array of function arguments.
 *
 * @ingroup themeable
 */
function theme_pathinfo_complex_arguments($variables) {
  // kprint_r requires a user access check which seems to fail for us, even
  // as user 1. Use krumo_ob directly.
  $arg_output = krumo_ob($variables['arguments']);
  // @see pathinfo_init() for accompanying Javascript.
  $args_css_id = 'pathinfo-args-output';
  $link_css_id = 'pathinfo-args-showhide';
  $arg_output = <<<OUTPUT
<div id="$args_css_id">
  {$arg_output}
</div>
OUTPUT;

  $link = l(t($variables['link_text']), $variables['path'], array(
    'fragment' => $args_css_id,
    'attributes' => array(
      'id' => $link_css_id,
      'title' => t('Show arguments'),
    ),
  ));

  
  $output = $link . $arg_output;
  return $output;
}