<?php

namespace Drupal\steam_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\steam_login\Data;
use Drupal\steam_api\ISteamUserInterface;
use Drupal\user\UserInterface;
use LightOpenID;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Steam OpenId Authentification.
 */
class OpenIdAuth extends ControllerBase {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorage
   */
  protected $userStorage;

  /**
   * ISteamUser webservice.
   *
   * @var \Drupal\steam_api\ISteamUserInterface
   */
  protected $iSteamUser;

  /**
   * Steam OpenIdAuth constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\steam_api\ISteamUserInterface $isteam_user
   *   The iSteamUser webservice.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    ISteamUserInterface $isteam_user
  ) {
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->currentUser = $current_user;
    $this->iSteamUser = $isteam_user;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('steam_api.user')
    );
  }

  /**
   * Steam openid callback.
   *
   * @return mixed
   *   Either a redirect to the Steam Open Id page or a renderable array.
   */
  public function content() {
    $build = [];

    if ($this->currentUser->isAnonymous()) {
      $callback_url = Url::fromRoute('steam_login.openid', [], ['absolute' => TRUE])
        ->toString(TRUE);

      $this->openid = new LightOpenID($callback_url->getGeneratedUrl());
      $this->openid->identity = Data::STEAM_OPENID_URL;

      if (!$this->openid->mode) {
        return $this->redirectToSteamLoginForm($callback_url);
      }

      if ($this->openid->mode == 'cancel') {
        return $this->buildCanceledContent($build);
      }

      if ($this->openid->validate()) {
        $steamcommunity_id = $this->getSteamCommunityId($this->openid->identity);
        $player_summaries = current($this->iSteamUser
          ->getPlayerSummaries($steamcommunity_id));

        if (!($user = $this->isSteamIdAlreadyUsed($steamcommunity_id))) {
          $user = $this->steamOauthCreateUser($steamcommunity_id, $player_summaries['personaname'] ?? '');
        }

        return $this->userConnectFromSteam($user);
      }
    }

    $build['already_logged'] = [
      '#markup' => 'You are already connected.',
    ];

    return $build;
  }

  /**
   * Get Steam community Id from an Openid identity.
   *
   * @param [type] $identity
   *   The steam Open Id identity.
   *
   * @return string
   *   The user Steam 64 Id.
   */
  protected function getSteamCommunityId($identity) {
    preg_match(Data::STEAM_OPENID_COMMUNITYID_REGEX, $identity, $matches);

    return $matches[1];
  }

  /**
   * Build the content that will be rendered when the user cancels the process.
   *
   * @param array &$build
   *   A render array.
   */
  protected function buildCanceledContent(array &$build) {
    $build['canceled'] = [
      '#markup' => 'You canceled the action.',
    ];
  }

  /**
   * Redirect the user to the steam login form.
   *
   * @param Drupal\Core\GeneratedUrl $callback_url
   *   The URL that will be called after the steam form has been supplied.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirection the user will follow.
   */
  protected function redirectToSteamLoginForm(GeneratedUrl $callback_url) {
    $this->openid->identity = Data::STEAM_OPENID_URL;
    $auth_uri = Url::fromUri($this->openid->authUrl())->toString(TRUE);
    $response = new TrustedRedirectResponse($auth_uri->getGeneratedUrl());
    $response->addCacheableDependency($callback_url);
    $response->addCacheableDependency($auth_uri);

    return $response;
  }

  /**
   * Creates a drupal user from steam community id.
   *
   * @param string $steamcommunity_id
   *   The user steam community id.
   * @param string $steam_nickname
   *   The user steam community nickname.
   *
   * @return false|\Drupal\user\UserInterface
   *   Either a user object or FALSE.
   *
   * @todo Allow other modules to alter user before save.
   */
  protected function steamOauthCreateUser(string $steamcommunity_id, string $steam_nickname) {
    if (empty($steamcommunity_id)) {
      return FALSE;
    }

    $user_infos = [
      'name' => "steam-$steamcommunity_id",
      'pass' => '',
      'status' => 1,
      'field_steam64id' => $steamcommunity_id,
      'field_steam_username' => urlencode($steam_nickname),
    ];

    if ($user = $this->userStorage->create($user_infos)) {
      $user->save();
      return $user;
    }
    return FALSE;
  }

  /**
   * Checks if a user already exists with the given steam community id.
   *
   * @param string $steamcommunity_id
   *   The user steam community id.
   *
   * @return false|\Drupal\user\UserInterface
   *   Either a user object or FALSE.
   */
  protected function isSteamIdAlreadyUsed(string $steamcommunity_id) {
    if ($users = $this->userStorage->loadByProperties(['field_steam64id' => $steamcommunity_id])) {
      return current($users);
    }

    return FALSE;
  }

  /**
   * Connect the user from steam.
   *
   * @param \Drupal\user\UserInterface $user
   *   Log the current user as the user we give.
   */
  protected function userConnectFromSteam(UserInterface $user) {
    user_login_finalize($user);
    $user_url = Url::fromRoute('entity.user.canonical', ['user' => $user->id()]);
    $response = new TrustedRedirectResponse($user_url->toString(TRUE)->getGeneratedUrl());
    $response->addCacheableDependency($user);

    return $response;
  }

}
