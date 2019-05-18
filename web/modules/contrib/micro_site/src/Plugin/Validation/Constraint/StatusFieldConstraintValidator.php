<?php

namespace Drupal\micro_site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use GuzzleHttp\Exception\RequestException;

/**
 * Validates that the DNS for a site url is well configured vefore publishing a site.
 */
class StatusFieldConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\micro_site\Entity\SiteInterface $entity */
    $entity = $items->getEntity();
    $registered = $entity->isRegistered();
    $status = $item->value;

    if ($status && !$registered) {
      $this->context->addViolation($constraint->message, []);
    }

  }

}
