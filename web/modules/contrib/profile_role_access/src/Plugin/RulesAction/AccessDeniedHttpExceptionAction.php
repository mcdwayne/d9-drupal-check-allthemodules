<?php

namespace Drupal\profile_role_access\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides an 'Access denied' action.
 *
 * @RulesAction(
 *   id = "rules_access_denied",
 *   label = @Translation("Access denied HTTP exception"),
 *   category = @Translation("System")
 * )
 */
class AccessDeniedHttpExceptionAction extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    throw new AccessDeniedHttpException();
  }

}
