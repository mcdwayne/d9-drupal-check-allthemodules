<?php

namespace Drupal\reactify_auth\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication provider.
 */
class JsonAuth implements AuthenticationProviderInterface {
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * User auth service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory interface.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   User interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manger interface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, UserAuthInterface $user_auth, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->userAuth = $user_auth;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks whether authentication method applies.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return bool
   *   Returns true if this authentication method should apply.
   */
  public function applies(Request $request) {
    $content = json_decode($request->getContent());
    return isset($content->name, $content->pass) && !empty($content->name) && !empty($content->pass) && !isset($content->mail);
  }

  /**
   * Authenticates the user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|\Drupal\Core\Session\AccountInterface|null
   *   Returns account interface or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function authenticate(Request $request) {
    $content = json_decode($request->getContent());
    $username = $content->name;
    $password = $content->pass;
    $uid = $this->userAuth->authenticate($username, $password);

    if ($uid) {
      return $this->entityTypeManager->getStorage('user')->load($uid);
    }
    return [];
  }

}
