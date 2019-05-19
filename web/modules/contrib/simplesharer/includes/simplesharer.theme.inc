<?php

use Drupal\Core\Url;
/**
 * Implements hook_theme().
 */
function simplesharer_theme() {
  return array(
    'simplesharer_symbol_button' => array(
      'variables' => array(
        'buttonname'            => NULL,
        'textname'              => NULL,
        'titletext'             => NULL,
        'url'                   => NULL,
        'iconlibrary'           => NULL,
        'awesomename'           => NULL,
        'foundationname'        => NULL,
        'actiontext'           => NULL,
      ),
    ),
    'simplesharer' => array(
      'variables' => array(
        'simplesharer_facebook_button' => NULL,
        'simplesharer_twitter_button' => NULL,
        'simplesharer_pinterest_button' => NULL,
        'simplesharer_linkedin_button' => NULL,
        'simplesharer_tumblr_button' => NULL,
        'simplesharer_reddit_button' => NULL,
        'simplesharer_email_button' => NULL,
        'simplesharer_style' => NULL,
      ),
    ),
  );
}

/**
 * Custom function to assemble renderable array for block content.
 *
 * @param string $config
 *   Array of configuration variables.
 *
 * @return array
 *
 *   returns a renderable array of block content.
 */
function simplesharer_content( $config ) {
  $block = array();
  $icon_library = $config['simplesharer_library'];
  $current_path = Url::fromRoute('<current>', [], ['absolute' => TRUE])->toString();
  $full_url = urlencode($current_path);
  $title = urlencode('temp');
  $module_path = drupal_get_path('module', 'simplesharer');

  $logo = urlencode(theme_get_setting('logo.url'));

  $facebook = $config['simplesharer_facebook'];
  $twitter = $config['simplesharer_twitter'];
  $pinterest = $config['simplesharer_pinterest'];
  $linkedin = $config['simplesharer_linkedin'];
  $tumblr = $config['simplesharer_tumblr'];
  $reddit = $config['simplesharer_reddit'];
  $email = $config['simplesharer_email'];

  $style = $config['simplesharer_style'];

  $markup_content = '';

  if ($facebook) {
    $facebook_button_array = array(
      '#theme'              => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'facebook',
      '#awesomename'         => 'fa-facebook',
      '#foundationname'      => 'fi-social-facebook',
      '#titletext'           => 'Facebook',
      '#url'                 => 'https://www.facebook.com/sharer/sharer.php?u=' . $full_url,
      '#actiontext'          => 'share',
    );
    $facebook_button = drupal_render($facebook_button_array);
  }
  if ($twitter) {
    $twitter_button_array = array(
      '#theme'              => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'twitter',
      '#awesomename'         => 'fa-twitter',
      '#foundationname'      => 'fi-social-twitter',
      '#titletext'           => 'Twitter',
      '#url'                 => 'https://twitter.com/intent/tweet/?url=' . $full_url . '&text=' . $title,
      '#actiontext'          => 'tweet',
    );
    $twitter_button = drupal_render($twitter_button_array);
  }
  if ($pinterest) {
    $pinterest_button_array = array(
      '#theme'               => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'pinterest',
      '#awesomename'         => 'fa-pinterest',
      '#foundationname'      => 'fi-social-pinterest',
      '#titletext'           => 'Pinterest',
      '#url'                 => 'https://www.pinterest.com/pin/create/button/?url=' . $full_url .
        '&media=' . $logo . '&description=' . $title,
      '#actiontext'          => 'pin',
    );
    $pinterest_button = drupal_render($pinterest_button_array);
  }
  if ($linkedin) {
    $linkedin_button_array = array(
      '#theme'              => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'linkedin',
      '#awesomename'         => 'fa-linkedin',
      '#foundationname'      => 'fi-social-linkedin',
      '#titletext'           => 'LinkedIN',
      '#url'                 => 'https://www.linkedin.com/shareArticle?mini=true&url=' . $full_url,
      '#actiontext'          => 'share',
    );
    $linkedin_button = drupal_render($linkedin_button_array);
  }
  if ($tumblr) {
    $tumblr_button_array = array(
      '#theme'               => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'tumblr',
      '#awesomename'         => 'fa-tumblr',
      '#foundationname'      => 'fi-social-tumblr',
      '#titletext'           => 'Tumblr',
      '#url'                 => 'https://www.tumblr.com/share/link?url=' . $full_url,
      '#actiontext'          => 'blog',
    );
    $tumblr_button = drupal_render($tumblr_button_array);
  }
  if ($reddit) {
    $reddit_button_array = array(
      '#theme'               => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'reddit',
      '#awesomename'         => 'fa-reddit',
      '#foundationname'      => 'fi-social-reddit',
      '#titletext'           => 'Reddit',
      '#url'                 => 'https://www.reddit.com/submit?url=' . $full_url,
      '#actiontext'          => 'submit',
    );
    $reddit_button = drupal_render($reddit_button_array);
  }
  if ($email) {
    $email_button_array = array(
      '#theme'               => 'simplesharer_symbol_button',
      '#iconlibrary'         => $icon_library,
      '#buttonname'          => 'email',
      '#awesomename'         => 'fa-envelope',
      '#foundationname'      => 'fi-mail',
      '#titletext'           => 'E-mail',
      '#url'                 => 'mailto:?subject=' . $title .
        '&body=' . $title . "%20:%20" . $full_url,
      '#actiontext'          => 'send',
    );
  }
  $markup_content_array = array(
    '#theme'                          => 'simplesharer',
    '#simplesharer_facebook_button'   => $facebook_button_array,
    '#simplesharer_twitter_button'    => $twitter_button_array,
    '#simplesharer_pinterest_button'  => $pinterest_button_array,
    '#simplesharer_linkedin_button'   => $linkedin_button_array,
    '#simplesharer_tumblr_button'     => $tumblr_button_array,
    '#simplesharer_reddit_button'     => $reddit_button_array,
    '#simplesharer_email_button'      => $email_button_array,
    '#simplesharer_style'             => $style,
  );
  $markup_content = drupal_render($markup_content_array);

  $block = array(
    '#markup' => $markup_content,
    '#attached' => array(
      'library' => array(
        'simplesharer/' . $style,
        'simplesharer/' . $icon_library,
      ),
    ),
  );

  return $block;
}
