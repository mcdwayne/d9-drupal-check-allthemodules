<?php

namespace Drupal\twitterlogin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;
use Drupal\twitterlogin\Plugin\Network\TwitterOAuth;

/**
 * Provides a Twitter OAuth Login Block
 *
 * @Block(
 *   id = "twitter_login_block",
 *   admin_label = @Translation("Twitter OAuth Login"),
 *   category = @Translation("Blocks")
 * )
 */
class TwitterLoginBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $twitterauthUrl = 'Empty Content';
    $icon_url = '';

    $consumerKeyConfig = \Drupal::config('twitterlogin.settings');
    $consumerKey = $consumerKeyConfig->get('consumer_key');
    $consumerSecretConfig = \Drupal::config('twitterlogin.settings');
    $consumerSecret = $consumerSecretConfig->get('consumer_secret');
    $redirectURLConfig = \Drupal::config('twitterlogin.settings');
    $redirectURL = $redirectURLConfig->get('redirect_url');

    if ($consumerKey == '' || $consumerSecret == '' || $redirectURL == '' ) {
      drupal_set_message($this->t('Please Provide Valid Twitter OAUTH API'), 'error');
    }

    //If OAuth token not matched.
    if (isset($_REQUEST['oauth_token']) && $_SESSION['token'] !== $_REQUEST['oauth_token']) {
      //Remove token from session.
      unset($_SESSION['token']);
      unset($_SESSION['token_secret']);
    }

    //If user already verified.
    if (isset($_SESSION['status']) && $_SESSION['status'] == 'verified' && !empty($_SESSION['request_vars'])) {
      // Load the current user.
      $user = User::load(\Drupal::currentUser()->id());
      $email = $user->get('mail')->value;
      return [
      '#markup' => $email,
        '#cache'  => [
          'max-age' => 0,
        ],
      ];
    }
    else {
      $auth_url = TwitterOAuth::requestToken($consumerKey, $consumerSecret, $redirectURL);

      $display = \Drupal::config('twitterlogin.icon.settings')->get('display');
      $display_url = \Drupal::config('twitterlogin.icon.settings')->get('display_url');

      $path = drupal_get_path('module', 'twitterlogin');

      if (isset($display_url) && $display_url!='') {
        $icon_url = '<img src = '.$display_url.' />';
      }
      else {
        if ($display == 0) {
          $icon_url = '<img src = "/'. $path .'/images/sign-in-with-twitter.png" border="0">';
        }
        if ($display == 1) {         
          $icon_url = '<img src = "/'. $path .'/images/twitter-icon.png" border="0">';
        }
      }

      if ($auth_url == '404') {
        $twitterauthUrl = $icon_url;
        drupal_set_message($this->t('Please Provide Valid Twitter OAUTH API'), 'error');
        return [
          '#markup' => $twitterauthUrl,
          '#cache'  => [
            'max-age' => 0,
          ],
        ];
        return false;
      }
      else {
        $twitterauthUrl = '<a href="'. $auth_url .'">'. $icon_url .'</a>';
      }
    }

    return [
      '#markup' => $twitterauthUrl,
      '#cache'  => [
        'max-age' => 0,
      ],
    ];
  }

}