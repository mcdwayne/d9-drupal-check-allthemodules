<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Drupal\user\Entity\User;

/**
 * @Healthcheck(
 *  id = "superuser",
 *  label = @Translation("Superuser"),
 *  description = "Checks the superuser account.",
 *  tags = {
 *   "security",
 *  }
 * )
 */
class Superuser extends HealthcheckPluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    /** @var \Drupal\user\Entity\User $user */
    if ($user = User::load(1)) {
      $findings[] = $this->checkStatus($user);
    }
    else {
      $findings[] = $this->notPerformed('superuser.status');
    }

    return $findings;
  }

  /**
   * Checks if the user is not blocked.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   */
  protected function checkStatus($user) {
    $status = $user->get('status')->value;

    if ($status != 1) {
      return $this->actionRequested('superuser.status');
    }

    return $this->noActionRequired('superuser.status');
  }
}
