<?php

namespace Drupal\entity_counter\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\entity_counter\Plugin\EntityCounterSourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is a valid entity source ID.
 */
class CounterSourceIdFieldValueValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity counter source manager.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterSourceManager
   */
  protected $entityCounterSourceManager;

  /**
   * Constructs a new CounterSourceIdFieldValueValidator object.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceManager $entity_counter_source_manager
   *   The entity counter source manager.
   */
  public function __construct(EntityCounterSourceManager $entity_counter_source_manager) {
    $this->entityCounterSourceManager = $entity_counter_source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_counter.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    if (!$sources = $this->entityCounterSourceManager->getDefinition($item->value, FALSE)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('@plugin_id', $item->value)
        ->addViolation();
    }
  }

}
