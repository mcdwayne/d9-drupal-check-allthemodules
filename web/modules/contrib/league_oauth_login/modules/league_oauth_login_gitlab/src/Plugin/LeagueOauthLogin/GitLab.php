<?php

namespace Drupal\league_oauth_login_gitlab\Plugin\LeagueOauthLogin;

use Drupal\league_oauth_login\LeagueOauthLoginPluginBase;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Omines\OAuth2\Client\Provider\Gitlab as GitlabProvider;

/**
 * Example plugin implementation of the league_oauth_login.
 *
 * @LeagueOauthLogin(
 *   id = "gitlab",
 *   label = @Translation("Gitlab"),
 *   description = @Translation("Gitlab login.")
 * )
 */
class GitLab extends LeagueOauthLoginPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAuthUrlOptions() {
    return [
      'scope' => ['read_user', 'read_repository', 'api'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return new GitlabProvider([
      'clientId' => $this->configFactory->get('league_oauth_login_gitlab.settings')->get('clientId'),
      'clientSecret' => $this->configFactory->get('league_oauth_login_gitlab.settings')->get('clientSecret'),
      'redirectUri' => $this->configFactory->get('league_oauth_login_gitlab.settings')->get('redirectUri'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserName(ResourceOwnerInterface $owner) {
    return $owner->getUserName();
  }

}
