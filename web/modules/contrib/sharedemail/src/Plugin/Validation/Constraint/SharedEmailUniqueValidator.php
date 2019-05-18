<?php

namespace Drupal\sharedemail\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Class SharedEmailUniqueValidator.
 *
 * @package Drupal\sharedemail\Plugin\Validation\Constraint
 */
class SharedEmailUniqueValidator extends UniqueFieldValueValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }
    if (\Drupal::currentUser()->getAccount()->hasPermission('create shared email account')) {
      $allowed = \Drupal::config('sharedemail.settings')->get('sharedemail_allowed');
      if (empty($allowed) || stripos($allowed, $item->value) !== FALSE) {
        return;
      }
    }
    parent::validate($items, $constraint);
  }

}
