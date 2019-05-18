<?php

namespace Drupal\decoupled_auth\Plugin\Validation\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserMailRequiredValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Checks if the user's email address is provided if required.
 *
 * The user mail field is NOT required if account originally had no mail set
 * and the user performing the edit has 'administer users' permission.
 * This allows users without email address to be edited and deleted.
 */
class DecoupledAuthUserMailRequiredValidator extends UserMailRequiredValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemListInterface $items */
    /* @var \Drupal\decoupled_auth\DecoupledAuthUserInterface $account */
    // If this account is decoupled.
    $account = $items->getEntity();

    // We only need to perform required validation if we are not decoupled.
    if (!$account->isDecoupled()) {
      parent::validate($items, $constraint);
    }
  }

}
