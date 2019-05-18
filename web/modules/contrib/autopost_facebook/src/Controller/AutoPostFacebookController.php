<?php

namespace Drupal\autopost_facebook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config;
use Facebook\Facebook;
use Zend\Diactoros\Response\RedirectResponse;
use Drupal\Core\Url;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

/**
 * Manages requests to Facebook.
 */
class AutoPostFacebookController extends ControllerBase {

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * FacebookPostController constructor.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The network plugin manager.
   */
  public function __construct(Config $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->getEditable('autopost_facebook.settings')
    );
  }

  /**
   * Redirects user to Facebook for authentication.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   Redirects to Facebook.
   */
  public function auth() {
    // Documentation in https://github.com/facebook/php-graph-sdk/blob/5.5/docs/examples/facebook_login.md
    $fb = new Facebook([
      'app_id' => $this->config->get('app_id'),
      'app_secret' => $this->config->get('app_secret'),
      'default_graph_version' => 'v2.10',
    ]);
    $helper = $fb->getRedirectLoginHelper();

    $permissions = ['publish_actions', 'manage_pages', 'publish_pages'];

    $loginUrl = Url::fromRoute('autopost_facebook.callback');
    $loginUrl->setAbsolute(TRUE);
    $url = $helper->getLoginUrl($loginUrl->toString(), $permissions);

    return new RedirectResponse($url);
  }

  /**
   * Callback function for the authentication process.
   */
  public function callback() {
    // Documentation in https://github.com/facebook/php-graph-sdk/blob/5.5/docs/examples/facebook_login.md
    $fb = new Facebook([
      'app_id' => $this->config->get('app_id'),
      'app_secret' => $this->config->get('app_secret'),
      'default_graph_version' => 'v2.10',
    ]);
    $helper = $fb->getRedirectLoginHelper();

    try {
      $accessToken = $helper->getAccessToken();
      if (isset($accessToken)) {
        // The OAuth 2.0 client handler helps us manage access tokens.
        $oAuth2Client = $fb->getOAuth2Client();

        // Get the access token metadata from /debug_token.
        $tokenMetadata = $oAuth2Client->debugToken($accessToken);

        // Exchanges a short-lived access token for a long-lived one.
        if (!$accessToken->isLongLived()) {
          $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
        }
        // Save the access token.
        $this->config
          ->set('access_token', (string) $accessToken->getValue())
          ->set('user_id', $tokenMetadata->getUserId())
          ->save();
      }
      else {
        if ($helper->getError()) {
          $message = $this->t('Error description: %message', ['%message' => $helper->getErrorDescription()]);
          drupal_set_message($message, 'error');
          return;
        }
        else {
          $message = $this->t('Bad request');
          drupal_set_message($message, 'error');
          return;
        }
      }
    }
    catch (FacebookResponseException $e) {
      $message = $this->t('Graph returned an error: %message', ['%message' => $e->getMessage()]);
      drupal_set_message($message, 'error');
    }
    catch (FacebookSDKException $e) {
      $message = $this->t('Facebook SDK returned an error: %message', ['%message' => $e->getMessage()]);
      drupal_set_message($message, 'error');
    }

    return $this->redirect('autopost_facebook.accounts_settings.login');
  }

  /**
   * Delete Facebook user account.
   */
  public function deleteUser() {
    $this->config
      ->clear('access_token')
      ->clear('user_id')
      ->save();
    return $this->redirect('autopost_facebook.accounts_settings.login');
  }

  /**
   * The account settings page.
   */
  public function accountSettings() {

    $output['table'] = [
      '#type' => 'table',
      '#header' => [$this->t('User ID'), $this->t('Operations')],
      '#empty' => $this->t('First add App ID and secret then register a Facebook account'),
    ];

    if ($this->config->get('access_token') and $this->config->get('user_id')) {
      $output['table'][0]['user_id'] = [
        '#type' => 'link',
        '#title' => '@' . $this->config->get('user_id'),
        '#url' => Url::fromUri('https://facebook.com/' . $this->config->get('user_id')),
      ];
      $output['table'][0]['operations'] = [
        '#type' => 'operations',
        '#links' => [
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('autopost_facebook.accounts_settings.delete_user'),
          ],
        ],
      ];
    }
    else {
      $output['button'] = [
        '#type' => 'link',
        '#title' => $this->t("Add account"),
        '#attributes' => [
          'class' => ['button'],
        ],
        '#url' => Url::fromRoute('autopost_facebook.auth'),
      ];
    }

    return $output;
  }

}
