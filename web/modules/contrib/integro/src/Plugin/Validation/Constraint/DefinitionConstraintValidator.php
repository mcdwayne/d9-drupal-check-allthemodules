<?php

namespace Drupal\integro\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\integro\DefinitionManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the definition constraint.
 */
class DefinitionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The integration definition manager.
   *
   * @var \Drupal\integro\DefinitionManagerInterface
   */
  private $definitionManager;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\integro\DefinitionManagerInterface $definition_manager
   *   The integration definition manager.
   */
  public function __construct(DefinitionManagerInterface $definition_manager) {
    $this->definitionManager = $definition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('integro_definition.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!isset($this->definitionManager->getDefinitions()[$value['definition']])) {
      $this->context->addViolation($constraint->invalidDefinition, ['@definition' => $value['definition']]);
    }
  }

}
