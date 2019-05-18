<?php

namespace Drupal\entityqueryapi;
 

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\ParamConverter\ParamConverterInterface;

use Symfony\Component\Routing\Route;

/**
 * Class EntityTypeParamConverter.
 */
class EntityTypeParamConverter implements ParamConverterInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'entityqueryapi.entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->entityTypeManager->getDefinition($value, FALSE);
  }

}
