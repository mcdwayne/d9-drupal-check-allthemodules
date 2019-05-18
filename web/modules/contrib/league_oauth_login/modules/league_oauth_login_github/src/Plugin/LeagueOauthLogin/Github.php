<?php

namespace Drupal\league_oauth_login_github\Plugin\LeagueOauthLogin;

use Drupal\league_oauth_login\LeagueOauthLoginPluginBase;
use League\OAuth2\Client\Provider\Github as GithubProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Example plugin implementation of the league_oauth_login.
 *
 * @LeagueOauthLogin(
 *   id = "github",
 *   label = @Translation("Github"),
 *   description = @Translation("Github login.")
 * )
 */
class Github extends LeagueOauthLoginPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAuthUrlOptions() {
    return [
      'scope' => ['user:email'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return new GithubProvider([
      'clientId' => $this->configFactory->get('league_oauth_login_github.settings')->get('clientId'),
      'clientSecret' => $this->configFactory->get('league_oauth_login_github.settings')->get('clientSecret'),
      'redirectUri' => $this->configFactory->get('league_oauth_login_github.settings')->get('redirectUri'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserName(ResourceOwnerInterface $owner) {
    return $owner->getNickName();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail(ResourceOwnerInterface $owner, $access_token) {
    if ($email = parent::getEmail($owner, $access_token)) {
      return $email;
    }
    // Try the mail endpoint.
    $provider = $this->getProvider();
    $req = $provider->getAuthenticatedRequest($provider::METHOD_GET, $provider->apiDomain . '/user/emails', $access_token);
    $res = $provider->getParsedResponse($req);
    // Lets hope there is one here.
    if (!empty($res[0]['email'])) {
      return $res[0]['email'];
    }
  }

}
