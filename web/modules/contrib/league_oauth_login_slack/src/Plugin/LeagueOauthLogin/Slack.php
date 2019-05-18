<?php

namespace Drupal\league_oauth_login_slack\Plugin\LeagueOauthLogin;

use AdamPaterson\OAuth2\Client\Provider\Slack as SlackProvider;
use Drupal\league_oauth_login\LeagueOauthLoginPluginBase;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Slack login.
 *
 * @LeagueOauthLogin(
 *   id = "slack",
 *   label = @Translation("Slack"),
 *   description = @Translation("Slack login.")
 * )
 */
class Slack extends LeagueOauthLoginPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAuthUrlOptions() {
    return [
      'scope' => ['identity.email', 'identity.basic'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return new SlackProvider([
      'clientId' => $this->configFactory->get('league_oauth_login_slack.settings')->get('clientId'),
      'clientSecret' => $this->configFactory->get('league_oauth_login_slack.settings')->get('clientSecret'),
      'redirectUri' => $this->configFactory->get('league_oauth_login_slack.settings')->get('redirectUri'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserName(ResourceOwnerInterface $owner) {
    /** @var \AdamPaterson\OAuth2\Client\Provider\SlackResourceOwner $owner */
    return $owner->getName();
  }

}
