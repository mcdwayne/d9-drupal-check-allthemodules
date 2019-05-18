<?php

namespace Drupal\dea_magic\Plugin\dea;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\dea\Annotation\RequirementDiscovery;
use Drupal\dea_magic\OperationReferenceScanner;
use Drupal\dea\RequirementDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds all related entities with a matching operation field to the list
 * of requirements of a target.
 * 
 * @RequirementDiscovery(
 *   id = "entity_reference_requirements",
 *   label = @Translation("Referenced requirements")
 * )
 */
class EntityReferenceRequirementDiscovery extends PluginBase implements RequirementDiscoveryInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\dea_magic\OperationReferenceScanner
   */
  protected $scanner;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('dea.scanner'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, OperationReferenceScanner $scanner) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scanner = $scanner;
  }

  /**
   * {@inheritdoc}
   */
  public function requirements(EntityInterface $subject, EntityInterface $target, $operation) {
    $entities = [];
    foreach ($this->scanner->operationReferences($subject, $target, $operation) as $reference) {
      $entities[] = $reference;
    }
    return $entities;
  }

}


