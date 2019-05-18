<?php

namespace Drupal\cg_payment\Plugin\Validation\Constraint;

use Drupal\cg_payment\Manager\RequestManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the terminal number.
 */
class ValidTerminalValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The request manager.
   *
   * @var \Drupal\cg_payment\Manager\RequestManager
   */
  protected $requestManager;

  /**
   * Creates a new ConstraintValidator instance.
   */
  public function __construct(RequestManager $requestManager) {
    $this->requestManager = $requestManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cg_payment.cg_request_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $terminal_id_field_name = $constraint->terminal_id_field_name;
    $mid_field_name = $constraint->mid_field_name;

    $parent_entity = $entity->getEntity();

    // Validate the field names.
    if (!$parent_entity->hasField($terminal_id_field_name) || !$parent_entity->hasField($mid_field_name)) {
      throw new InvalidArgumentException();
    }

    // Get the IDs from the fields.
    if (empty($parent_entity->get($terminal_id_field_name)->value) || empty($parent_entity->get($mid_field_name)->value)) {
      $this->context->addViolation($constraint->message);
    }

    $terminal_id = $parent_entity->get($terminal_id_field_name)->value;
    $mid = $parent_entity->get($mid_field_name)->value;

    // Validate the IDs against CG.
    try {
      $url = $this->requestManager->requestPaymentFormUrl($terminal_id, $mid, '1', 'cg_payment@drupal.org', 'Test transction by cg_payment');
      if (empty($url)) {
        $this->context->addViolation($constraint->message);
      }
    }
    catch (\Exception $e) {
      $this->context->addViolation($constraint->message);
    }

  }

}
