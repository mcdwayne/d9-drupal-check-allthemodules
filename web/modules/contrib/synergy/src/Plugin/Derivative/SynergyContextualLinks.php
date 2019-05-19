<?php

namespace Drupal\synergy\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for synergy.
 *
 */
class SynergyContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SynergyRouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Create contextual links for translatable entity types.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id]['title'] = t('Synergy');
      $this->derivatives[$entity_type_id]['route_name'] = "entity.$entity_type_id.synergy";
      $this->derivatives[$entity_type_id]['group'] = $entity_type_id;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
