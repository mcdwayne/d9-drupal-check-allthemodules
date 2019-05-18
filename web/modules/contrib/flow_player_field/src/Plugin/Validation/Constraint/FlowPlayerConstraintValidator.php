<?php

namespace Drupal\flow_player_field\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\flow_player_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Flow Player providers.
 */
class FlowPlayerConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Flow Player provider manager service.
   *
   * @var \Drupal\flow_player_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * Create an instance of the validator.
   *
   * @param \Drupal\flow_player_field\ProviderManagerInterface $provider_manager
   *   The provider manager service.
   */
  public function __construct(ProviderManagerInterface $provider_manager) {
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flow_player_field.provider_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint) {
    if (!isset($field->value)) {
      return NULL;
    }

    $allowed_providers = $field->getFieldDefinition()
      ->getSetting('allowed_providers');
    $allowed_provider_definitions = $this->providerManager->loadDefinitionsFromOptionList($allowed_providers);

    if (FALSE === $this->providerManager->filterApplicableDefinitions($allowed_provider_definitions, $field->value)) {
      $this->context->addViolation($constraint->message);
    }
  }

}
