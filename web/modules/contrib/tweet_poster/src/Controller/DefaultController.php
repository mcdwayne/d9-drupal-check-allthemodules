<?php /**
 * @file
 * Contains \Drupal\tweet_poster\Controller\DefaultController.
 */

namespace Drupal\tweet_poster\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the tweet_poster module.
 */
class DefaultController extends ControllerBase {

  public function tweet_poster_twitterpage() {
    require_once drupal_get_path('module', 'tweet_poster') . '/tweet_poster_config.php';
    require_once libraries_get_path('twitteroauth') . '/twitteroauth/twitteroauth.php';
    require_once libraries_get_path('tmhOAuth') . '/tmhOAuth.php';
    if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
      return tweet_poster_redirect();
    }
    $filename = NULL;
    $mime = NULL;
    if (!empty($_SESSION['tweet_poster_imgpath'])) {
      $filename = drupal_realpath(\Drupal::service("stream_wrapper_manager")->getViaUri('public://')->getDirectoryPath() . '/' . $_SESSION['tweet_poster_imgpath'] . '');
      $handle = fopen($filename, "rb");
      $image = fread($handle, filesize($filename));
      $ext = explode('.', $_SESSION['tweet_poster_imgpath']);
      $mime = 'image/' . $ext[1];
    }
    // If access tokens are not available redirect to connect page.
    // Get user access tokens out of the session.
    $access_token = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : NULL;
    // Create a TwitterOauth object with consumer/user tokens.
    $connection = new TwitterOAuth(TWEET_POSTER_CONSUMER_KEY, TWEET_POSTER_CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    // If method is set change API call made. Test is called by default.
    $content = $connection->get('account/verify_credentials');
    $tmh_oauth = new tmhOAuth([
      'consumer_key' => TWEET_POSTER_CONSUMER_KEY,
      'consumer_secret' => TWEET_POSTER_CONSUMER_SECRET,
      'token' => $access_token['oauth_token'],
      'secret' => $access_token['oauth_token_secret'],
    ]);
    $delegator = 'http://api.twitpic.com/2/upload.json';
    $x_auth_service_provider = 'https://api.twitter.com/1.1/account/verify_credentials.json';
    tweet_poster_generate_verify_header($tmh_oauth, $x_auth_service_provider);
    // Sometime notices may come so used(@) to avoid.
    $params = @tweet_poster_generate_prepare_request($tmh_oauth, $x_auth_service_provider, TWEET_POSTER_TWEETPIC_KEY, $filename, $mime);
    // Post to OAuth Echo provider.
    $code = tweet_poster_make_request($tmh_oauth, $delegator, $params, FALSE, TRUE);
    $resp = @json_decode($tmh_oauth->response['response']);
    @$srtlen_respurl = strlen($resp->url) + 1;
    $post_status = substr($_SESSION['tweet_poster_status'], 0, 140 - $srtlen_respurl);
    @$params = ['status' => $post_status . ' ' . $resp->url];
    @$code = tweet_poster_make_request($tmh_oauth, $tmh_oauth->url('1.1/statuses/update'), $params, TRUE, FALSE);
    if ($code == 200) {
      unset($_SESSION['tweet_poster_status']);
      unset($_SESSION['tweet_poster_imgpath']);
      drupal_set_message(t('Twitter Post successfully'));
      if (!isset($_SESSION['tweet_poster_url_redirector'])) {
        drupal_goto('twitterpost');
      }
      else {
        drupal_goto($_SESSION['tweet_poster_url_redirector']);
      }
    }
    else {
      unset($_SESSION['tweet_poster_status']);
      unset($_SESSION['tweet_poster_imgpath']);
      drupal_set_message(t('Their is some issue while posting in twitter.'), 'error');
      if (!isset($_SESSION['tweet_poster_url_redirector'])) {
        drupal_goto('twitterpost');
      }
      else {
        drupal_goto($_SESSION['tweet_poster_url_redirector']);
      }
    }
    drupal_exit();
  }

  public function tweet_poster_callback() {
    global $base_url;
    require_once drupal_get_path('module', 'tweet_poster') . '/tweet_poster_config.php';
    require_once libraries_get_path('twitteroauth') . '/twitteroauth/twitteroauth.php';

    // If the oauth_token is old redirect to the connect page.
    if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
      $_SESSION['oauth_status'] = 'oldtoken';
      drupal_set_message(t('No longer available', 'error'));
      if (!isset($_SESSION['tweet_poster_url_redirector'])) {
        drupal_goto('twitterpost');
      }
      else {
        drupal_goto($_SESSION['tweet_poster_url_redirector']);
      }
      drupal_exit();
    }
    // Create TwitteroAuth object from default phase configuration.
    $connection = new TwitterOAuth(TWEET_POSTER_CONSUMER_KEY, TWEET_POSTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

    // Request access tokens from twitter.
    $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

    // Save the access tokens these would be saved in a session for future use.
    $_SESSION['access_token'] = $access_token;

    // Remove no longer needed request tokens.
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);

    // If HTTP response is 200 continue otherwise send to connect page to retry.
    if (200 == $connection->http_code) {
      // User has been verified and the access tokens saved for future use.
      $_SESSION['status'] = 'verified';
      header("Location: " . $base_url . '/twitterpost');
    }
    else {
      // Save HTTP status for error dialog on connnect page.
      drupal_set_message(t('Could not connect to Twitter. Refresh the page or try again later.', 'error'));
      if (!isset($_SESSION['tweet_poster_url_redirector'])) {
        drupal_goto('twitterpost');
      }
      else {
        drupal_goto($_SESSION['tweet_poster_url_redirector']);
      }
    }
  }

}
