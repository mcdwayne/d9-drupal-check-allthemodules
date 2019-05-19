<?php

/**
 * @file
 * Contains \Drupal\social_timeline\SocialTimelineManager.
 */

namespace Drupal\social_timeline;


/**
 * Social Timeline Manager Class.
 */
class SocialTimelineManager {
  /**
   * Default feeds.
   *
   * @return array
   *   The default feeds.
   */
  public static function getFeeds() {
    $feeds = array();

    $feeds['twitter'] = array(
      'data' => 'Username',
      'title' => 'Twitter',
      'auth' => 'library/twitter_oauth/user_timeline.php',
      'params' => array('screen_name'),
    );

    $feeds['twitter_hash'] = array(
      'data' => 'Hashtag (without the #)',
      'title' => 'Twitter Hashtag',
      'auth' => 'library/twitter_oauth/search.php',
      'params' => array('q'),
    );

    $feeds['facebook_page'] = array(
      'data' => 'Page ID',
      'title' => 'Facebook',
      'auth' => 'library/facebook_auth/facebook_page.php',
      'params' => array('page_id'),
    );

    $feeds['instagram'] = array(
      'data' => 'Username',
      'title' => 'Instagram',
      'auth' => 'library/instagram_auth/instagram.php',
      'params' => array('username'),
    );

    $feeds['instagram_hash'] = array(
      'data' => 'Hashtag (without the #)',
      'title' => 'Instagram Hashtag',
      'auth' => 'library/instagram_auth/instagram_hash.php',
      'params' => array('tag'),
    );

    $feeds['delicious'] = array(
      'data' => 'Username',
      'title' => 'Delicious',
    );

    $feeds['flickr'] = array(
      'data' => 'User ID',
      'title' => 'Flickr',
    );

    $feeds['flickr_hash'] = array(
      'data' => 'Hashtag (without the #)',
      'title' => 'Flickr Hashtag',
    );

    $feeds['google'] = array(
      'data' => 'Google+ is not adding RSS feed support oficially, so you will have to go to http://gplusrss.com/, login with your g+ account and use the RSS url in this option',
      'title' => 'G+ RSS',
    );

    $feeds['tumblr'] = array(
      'data' => 'Username',
      'title' => 'Tumblr',
    );

    $feeds['youtube'] = array(
      'data' => 'Username',
      'title' => 'Youtube',
      'auth' => 'library/youtube_auth/youtube.php',
      'params' => array('username'),
    );

    $feeds['youtube_search'] = array(
      'data' => 'Search',
      'title' => 'Youtube Search',
      'auth' => 'library/youtube_auth/youtube.php',
      'params' => array('q'),
    );

    $feeds['dribbble'] = array(
      'data' => 'Username',
      'title' => 'Dribbble',
    );

    $feeds['digg'] = array(
      'data' => 'Username',
      'title' => 'Digg',
    );

    $feeds['pinterest'] = array(
      'data' => 'Username',
      'title' => 'Pinterest',
    );

    $feeds['vimeo'] = array(
      'data' => 'Username',
      'title' => 'Vimeo',
    );

    $feeds['soundcloud'] = array(
      'data' => 'ID',
      'title' => 'Soundcloud',
    );

    return $feeds;
  }

  /**
   * Sort the feeds by the weight.
   *
   * @param array $a
   *   The first element to compare against.
   * @param array $b
   *   The second element to compare against.
   *
   * @return bool
   *   Boolean for weighting.
   */
  public static function sortFeeds($a, $b) {
    // Make sure we are only dealing with feed data.
    if (!is_array($a) || !is_array($b)) {
      return 0;
    }

    if (!isset($a['data']) || !isset($b['data'])) {
      return 0;
    }

    if (!isset($a['#weight'])) {
      $a_weight = $a['weight'];
      $b_weight = $b['weight'];
    }
    else {
      $a_weight = $a['#weight'];
      $b_weight = $b['#weight'];
    }

    // If the feeds are equal in weight leave them at the same level.
    if ($a_weight == $b_weight) {
      return 0;
    }

    // If the feeds are different weights then sort them accordingly.
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  public static function underscoreToCamelCase($string, $first_char_caps = false) {
    if ($first_char_caps == true) {
      $string[0] = strtoupper($string[0]);
    }

    $func = create_function('$c', 'return strtoupper($c[1]);');
    return preg_replace_callback('/_([a-z])/', $func, $string);
  }

  /**
   * Format the default feeds to send to javascript.
   *
   * @param array $feeds
   *   The default feeds.
   *
   * @return array
   *   The formatted feeds array.
   */
  public static function formatDefaultFeeds($feeds = array()) {
    $default_feeds = static::getFeeds();
    isset($_SERVER['HTTPS']) ? $protocol = 'https://' : $protocol = 'http://';
    $module_full_path = $protocol . $_SERVER['HTTP_HOST']. base_path() . drupal_get_path('module', 'social_timeline');

    $feed_settings = array();
    foreach ($feeds as $k => $v) {
      if ($v['active']) {
        if (strstr($k, 'custom_')) {
          $custom_feeds[$k] = $v;
        }
        else {
          if (isset($default_feeds[$k]['auth'])) {
            if ($v['data'] != '') {
              $query = '?' . $default_feeds[$k]['params'][0] . '=' . $v['data'];
              $feed_settings[$k] = array(
                'data' => $module_full_path . '/' . $default_feeds[$k]['auth'] . $query,
                'limit' => $v['limit'],
              );
            }
          }
          else {
            $limit = (isset($v['limit'])) ? ', limit: \'' . $v['limit'] . '\'' : '';

            if ($v['data'] != '') {
              $feed_settings[$k] = array(
                'data' => $v['data'],
                'limit' => $v['limit'],
              );
            }
          }
        }
      }
    }

    return $feed_settings;
  }

  /**
   * Format the custom feeds to send to javascript.
   *
   * @param array $feeds
   *   The custom feeds.
   *
   * @return array
   *   The formatted feeds array.
   */
  public static function formatCustomFeeds($feeds = array()) {
    $custom_feeds = array();
    foreach ($feeds as $k => $v) {
      if (strpos($k, 'custom_') !== FALSE) {
        if ($v['active']) {
          $custom_feeds[$k] = array(
            'name' => $v['title_val'],
            'url' => $v['data'],
            'icon' => $v['icon'],
            'limit' => $v['limit'],
          );
        }
      }
    }

    return $custom_feeds;
  }
}
