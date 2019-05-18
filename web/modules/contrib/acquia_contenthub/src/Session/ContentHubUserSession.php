<?php

namespace Drupal\acquia_contenthub\Session;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;

/**
 * An account implementation representing a Content Hub user.
 *
 * @todo ensure this doesn't need any further refactoring.
 */
class ContentHubUserSession extends UserSession {

  /**
   * Role used to render Content Hub content.
   *
   * @var string
   */
  protected $renderRole;

  /**
   * Constructs a new Content Hub user session.
   *
   * @param string $render_role
   *   Role id.
   */
  public function __construct($render_role) {
    $this->renderRole = $render_role;
    parent::__construct(['roles' => $this->getContentHubRenderUserRoles($render_role)]);
  }

  /**
   * Obtains the user roles based on the module settings.
   *
   * @param string $render_role
   *   Role to view content.
   *
   * @return array
   *   Array of roles.
   */
  protected function getContentHubRenderUserRoles($render_role) {
    switch ($render_role) {
      case AccountInterface::ANONYMOUS_ROLE:
      case AccountInterface::AUTHENTICATED_ROLE:
        $roles = [$render_role];
        break;

      default:
        $roles = [
          AccountInterface::AUTHENTICATED_ROLE,
          $render_role,
        ];
        break;
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->renderRole !== AccountInterface::ANONYMOUS_ROLE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->renderRole === AccountInterface::ANONYMOUS_ROLE;
  }

}
