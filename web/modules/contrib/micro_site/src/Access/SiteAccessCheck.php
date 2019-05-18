<?php

namespace Drupal\micro_site\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\Routing\Route;

/**
 * @TODO remove - not used. check why domain do this.
 * Provides a global access check to ensure inactive sites are restricted.
 */
class SiteAccessCheck implements AccessCheckInterface {

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\siteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the object.
   *
   * @param SiteNegotiatorInterface $negotiator
   *   The domain negotiation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(SiteNegotiatorInterface $negotiator, ConfigFactoryInterface $config_factory) {
    $this->negotiator = $negotiator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $this->checkPath($route->getPath());
  }

  /**
   * {@inheritdoc}
   */
  public function checkPath($path) {
    $allowed_paths = $this->configFactory->get('domain.settings')->get('login_paths', '/user/login\r\n/user/password');
    if (!empty($allowed_paths)) {
      $paths = preg_split("(\r\n?|\n)", $allowed_paths);
    }
    if (!empty($paths) && in_array($path, $paths)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    /** @var \Drupal\micro_site\Entity\SiteInterface $site */
    $site = $this->negotiator->loadFromRequest();
    // No site, let it pass.
    if (empty($site)) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    // Active domain, let it pass.
    if ($site->isPublished()) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }
    // Unpubulished, require permissions.
    else {
      $permissions = array('administer sites entities', 'view unpublished site entities');
      $operator = 'OR';
      return AccessResult::allowedIfHasPermissions($account, $permissions, $operator)->setCacheMaxAge(0);
    }
  }

}
