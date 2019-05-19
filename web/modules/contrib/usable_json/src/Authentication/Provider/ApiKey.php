<?php

/**
 * @file
 * Contains \Drupal\basic_auth\Authentication\Provider\BasicAuth.
 */

namespace Drupal\usable_json\Authentication\Provider;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Authentication\AuthenticationProviderChallengeInterface;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * HTTP Basic authentication provider.
 */
class ApiKey implements AuthenticationProviderInterface, AuthenticationProviderChallengeInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserAuthInterface $user_auth, FloodInterface $flood, EntityManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
    $this->userAuth = $user_auth;
    $this->flood = $flood;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $api_key = $request->headers->get('X-API-KEY');
    $config = \Drupal::config('usable_json.api');

    if (!empty($config->get('api_key'))) {
      return $api_key == $config->get('api_key');
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $config = \Drupal::config('usable_json.api');
    return $this->entityManager->getStorage('user')
      ->load($config->get('api_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function challengeException(Request $request, \Exception $previous) {
    $site_name = $this->configFactory->get('system.site')->get('name');
    $challenge = SafeMarkup::format('Basic realm="@realm"', array(
      '@realm' => !empty($site_name) ? $site_name : 'Access restricted',
    ));
    return new UnauthorizedHttpException((string) $challenge, 'No authentication credentials provided.', $previous);
  }

}
