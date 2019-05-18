<?php

namespace Drupal\externalauth_gitlab\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Omines\OAuth2\Client\Provider\Gitlab;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LoginController.
 */
class LoginController extends ControllerBase {

  const OAUTH_2_STATE = 'oauth2state';

  /**
   * Drupal\externalauth\ExternalAuthInterface definition.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalauth;

  /**
   * The private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  private $tempstore;

  /**
   * Constructs a new LoginController object.
   */
  public function __construct(
    ExternalAuthInterface $external_auth,
    PrivateTempStoreFactory $temp_store_factory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->externalauth = $external_auth;
    $this->tempstore = $temp_store_factory->get('externalauth_gitlab');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('externalauth.externalauth'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Try to login via gitlab.
   */
  public function login(Request $request) {
    $config = $config = \Drupal::config('externalauth_gitlab.settings');
    if (empty($config->get('client_id')) || empty($config->get('client_secret')) || empty($config->get('domain'))) {
      throw new \InvalidArgumentException($this->t('Please finish setup of module first, missing config!'));
    }

    // Drupal caches redirects, which is not safe!
    \Drupal::service('page_cache_kill_switch')->trigger();

    $provider = new Gitlab([
      'clientId'          => $config->get('client_id'),
      'clientSecret'      => $config->get('client_secret'),
      'redirectUri'       => Url::fromRoute(
        '<current>',
        [],
        ['absolute' => TRUE]
      )->toString(TRUE)->getGeneratedUrl(),
      'domain'            => $config->get('domain'),
    ]);

    $code = $request->query->get('code');
    $state = $request->query->get('state');

    if (!$code) {

      // If we don't have an authorization code then get one.
      $authUrl = $provider->getAuthorizationUrl();
      $oauth2_state = $provider->getState();
      $this->tempstore->set(self::OAUTH_2_STATE, $oauth2_state);

      return new TrustedRedirectResponse($authUrl, 302);

    }
    elseif (!$state || ($state !== $this->tempstore->get(self::OAUTH_2_STATE))) {

      $saved_state = $this->tempstore->get(self::OAUTH_2_STATE);
      $this->tempstore->delete(self::OAUTH_2_STATE);
      throw new \RuntimeException('Invalid state');
    }
    else {

      // Try to get an access token (using the authorization code grant)
      $token = $provider->getAccessToken('authorization_code', [
        'code' => $code,
      ]);

      // Optional: Now you have a token you can look up a users profile data.
      try {

        // We got an access token, let's now get the user's details.
        $gitlab_user = $provider->getResourceOwner($token);
        $this->doLogin($gitlab_user, $token->getToken());
        drupal_set_message($this->t('Successfully logged in via gitlab.'));
        return new RedirectResponse(Url::fromRoute('<front>')->toString(), 302);

      }
      catch (\Exception $e) {
        drupal_set_message($this->t(
          'Could not log in via gitlab, error: %error',
          [
            '%error' => $e->getMessage(),
          ]
        ), 'error');
        return new RedirectResponse(Url::fromRoute('<front>')->toString(), 302);
      }
    }
  }

  /**
   * Login gitlab user.
   *
   * @param \League\OAuth2\Client\Provider\ResourceOwnerInterface $gitlab_user
   *   The gitlab user data.
   * @param string $getToken
   *   The token.
   *
   * @return bool|\Drupal\user\UserInterface
   *   The user entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function doLogin(ResourceOwnerInterface $gitlab_user, $getToken) {

    // Try login first:
    $account = $this->externalauth->login($gitlab_user->getId(), 'oauth2_gitlab');
    if ($account) {
      return $account;
    }

    $gitlab_data = $gitlab_user->toArray();
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->loadByProperties(
      [
        'mail' => $gitlab_data['email'],
      ]
    );
    if ($account) {
      $account = reset($account);
      $this->externalauth->linkExistingAccount(
        $gitlab_user->getId(),
        'oauth2_gitlab',
        $account
      );
    }
    else {
      throw new \RuntimeException($this->t(
        'Could not find gitlab user! Please ask your admin for help!'
      ));
    }

  }

}
