<?php

/**
 * @file
 * Contains \Drupal\freshdesk_sso\Controller\RedirectController.
 */

namespace Drupal\freshdesk_sso\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\freshdesk_sso\Entity\FreshdeskConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\freshdesk_sso\AuthenticationService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RedirectController.
 *
 * @package Drupal\freshdesk_sso\Controller
 */
class RedirectController extends ControllerBase {

  /**
   * Drupal\freshdesk_sso\AuthenticationService definition.
   *
   * @var \Drupal\freshdesk_sso\AuthenticationService
   */
  protected $freshdesk_sso_authentication;
  /**
   * {@inheritdoc}
   */
  public function __construct(AuthenticationService $freshdesk_sso_authentication) {
    $this->freshdesk_sso_authentication = $freshdesk_sso_authentication;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('freshdesk_sso.authentication')
    );
  }

  /**
   * Redirect.
   *
   * @return string
   *   Return Hello string.
   */
  public function ssoRedirect(FreshdeskConfig $freshdesk_config) {
    return new TrustedRedirectResponse($this->freshdesk_sso_authentication->buildUrl($freshdesk_config));
  }

  /**
   * Checks access for single sign on.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Check access for this account
   *
   * @return bool
   */
  public function access(AccountInterface $account, FreshdeskConfig $freshdesk_config) {
    return $account->hasPermission('sign into ' . $freshdesk_config->id()) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
