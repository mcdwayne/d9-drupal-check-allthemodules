<?php /**
 * @file
 * Contains \Drupal\jquery_social_stream\Controller\DefaultController.
 */

namespace Drupal\jquery_social_stream\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jquery_social_stream\Twitter\TwitterOAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the jquery_social_stream module.
 */
class DefaultController extends ControllerBase {


  public function jquery_social_stream_twitter_callback() {
    $config = \Drupal::config('jquery_social_stream.settings');

    $consumer_key = $config->get('twitter_api_key');
    $consumer_secret = $config->get('twitter_api_secret');
    $oauth_access_token = $config->get('twitter_access_token');
    $oauth_access_token_secret = $config->get('twitter_access_token_secret');

    switch ($_GET['url']) {
      case 'timeline':
        $rest = 'statuses/user_timeline';
        $params = Array(
          'count' => $_GET['count'],
          'include_rts' => $_GET['include_rts'],
          'exclude_replies' => $_GET['exclude_replies'],
          'screen_name' => $_GET['screen_name']
        );
        break;
      case 'search':
        $rest = "search/tweets";
        $params = array(
          'q' => $_GET['query'],
          'count' => $_GET['count'],
          'include_rts' => $_GET['include_rts']
        );
        break;
      case 'list':
        $rest = "lists/statuses";
        $params = array(
          'list_id' => $_GET['list_id'],
          'count' => $_GET['count'],
          'include_rts' => $_GET['include_rts']
        );
        break;
      default:
        $rest = 'statuses/user_timeline';
        $params = array(
          'count' => '20'
        );
        break;
    }

    $auth = new TwitterOAuth($consumer_key, $consumer_secret, $oauth_access_token, $oauth_access_token_secret);
    $get = $auth->get($rest, $params);

    $output = '';

    if (!$get) {
      $output .= 'An error occurs while reading the feed, please check your connection or settings';
    }
    elseif (isset($get->errors)) {
      foreach ($get->errors as $key => $val) {
        $output .= $val;
      }
    }
    else {
      $output .= $get;
    }

    return new Response($output);
  }
}
