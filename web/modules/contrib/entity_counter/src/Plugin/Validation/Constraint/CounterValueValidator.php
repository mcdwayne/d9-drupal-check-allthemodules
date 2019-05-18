<?php

namespace Drupal\entity_counter\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Number as NumberUtility;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_counter\Entity\CounterTransactionInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that an entity counter has a valid counter value.
 */
class CounterValueValidator extends ConstraintValidator {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }

    $values = NULL;
    if ($entity instanceof CounterTransactionInterface) {
      /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $entity */
      $counter = $entity->getEntityCounter();
      $values = [
        [
          'value' => $entity->getTransactionValue(),
          'field_name' => $this->t('Transaction value'),
        ],
      ];
    }
    else {
      /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity */
      $counter = $entity;
      $values = [
        [
          'value' => $counter->getMax(),
          'field_name' => $this->t('Maximum value'),
        ],
        [
          'value' => $counter->getInitialValue(),
          'field_name' => $this->t('Initial value'),
        ],
      ];
    }

    foreach ($values as $value) {
      if (!NumberUtility::validStep($value['value'], $counter->getStep(), $counter->getMin())) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('@field_name', $value['field_name'])
          ->addViolation();
      }
    }
  }

}
