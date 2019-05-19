<?php

namespace Drupal\x_reference\Plugin\Validation\Constraint;

use Drupal\x_reference\Entity\XReference;
use Drupal\x_reference\Entity\XReferenceType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class XReferencedEntityConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   *
   * @throws \RuntimeException
   */
  public function validate($value, Constraint $constraint) {
    // $this->context->getRoot()->getValue()
    $XReference = $value->getEntity();
    if (!$XReference instanceof XReference) {
      throw new \RuntimeException(strtr('Incorrect value->entity class. It should be: @x_class, received: @received_class', [
        '@x_class' => XReference::class,
        '@received_class' => get_class($XReference),
      ]));
    }
    /** @var XReferenceType $XReferenceType */
    $XReferenceType = $XReference->type->entity;
    $entitySource = $value->entity->entity_source->value;
    $entityType = $value->entity->entity_type->value;

    switch ($value->getName()) {
      case 'source_entity':
        $requiredEntitySource = $XReferenceType->source_entity_source;
        $requiredEntityType = $XReferenceType->source_entity_type;
        break;

      case 'target_entity':
        $requiredEntitySource = $XReferenceType->target_entity_source;
        $requiredEntityType = $XReferenceType->target_entity_type;
        break;

      default:
        throw new \RuntimeException('Incorrect field value (' . $value->getName() . ') passed to validator');
    }

    if ($requiredEntitySource !== $entitySource || $requiredEntityType !== $entityType) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ entity_source }}', $this->formatValue($entitySource))
        ->setParameter('{{ entity_type }}', $this->formatValue($entityType))
        ->setParameter('{{ mode }}', $this->formatValue($value->getName()))
        ->setParameter('{{ x_reference_type }}', $this->formatValue($XReferenceType->id()))
        ->addViolation();
    }
  }

}
