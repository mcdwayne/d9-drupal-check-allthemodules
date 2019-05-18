<?php

namespace Drupal\entity_usage_integrity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Exception;

/**
 * Provide functionality to validate entity usage integrity.
 */
trait IntegrityValidationTrait {

  /**
   * The entity usage integrity validator service.
   *
   * @var \Drupal\entity_usage_integrity\IntegrityValidator
   */
  protected $integrityValidator;

  /**
   * Entity usage integrity validation context.
   *
   * @var string
   */
  protected $validationContext;

  /**
   * The entity usage integrity configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $integrityConfig;

  /**
   * Injects the integrity validator service.
   *
   * @param \Drupal\entity_usage_integrity\IntegrityValidator $integrity_validator
   *   The entity usage integrity validator service.
   *
   * @return $this
   */
  public function setIntegrityValidator(IntegrityValidator $integrity_validator) {
    $this->integrityValidator = $integrity_validator;
    return $this;
  }

  /**
   * Set validation context.
   *
   * @param string $context
   *   The entity usage integrity validator service.
   *
   * @return $this
   */
  public function setValidationContext($context) {
    $this->validationContext = $context;
    return $this;
  }

  /**
   * Set entity usage integrity configuration.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   *
   * @return $this
   */
  public function setIntegrityConfig(ConfigFactoryInterface $config_factory) {
    $this->integrityConfig = $config_factory->get('entity_usage_integrity.settings');
    return $this;
  }

  /**
   * Validate usage integrity for given entity and get validated relations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $current_entity
   *   Entity for which we are validating entity usage integrity.
   *
   * @return \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationCollections
   *   Collection of relations for current entity with statuses.
   *
   * @throws \Exception
   *   If integrity validator or entity usage integrity validation context
   *   is not set.
   */
  public function getValidatedUsageRelations(EntityInterface $current_entity) {
    if (!$this->integrityValidator) {
      throw new Exception('Entity usage integrity validator not set.');
    }
    if (!$this->validationContext) {
      throw new Exception('Entity usage integrity validation context not set.');
    }
    return $this->integrityValidator->getValidatedUsageRelations($current_entity, $this->validationContext);
  }

  /**
   * Get usage integrity validation mode.
   *
   * Usage integrity check can work with two modes: block or warning.
   *
   * @return string
   *   Integrity validation mode
   *
   * @see IntegritySettingsForm::buildForm()
   */
  public function getIntegrityValidationMode() {
    return $this->integrityConfig->get('mode');
  }

}
