<?php

namespace Drupal\living_style_guide\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\living_style_guide\Controller\StyleGuideController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class GuideTypesBundlesLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * GuideTypesBundlesLocalTasks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $styleGuideController = StyleGuideController::create(\Drupal::getContainer());

    $entityTypes = $styleGuideController->getEntityTypes();
    $bundles = $styleGuideController->getAllBundles();

    foreach ($entityTypes as $entityType) {
      if (!isset($bundles[$entityType])) {
        continue;
      }

      $availableBundles = array_keys($bundles[$entityType]);
      $entityTypeLabel = $this->entityTypeManager->getDefinition($entityType)->getLabel();

      $this->derivatives['living_style_guide.guide.' . $entityType] = $base_plugin_definition;
      $this->derivatives['living_style_guide.guide.' . $entityType]['title'] = $entityTypeLabel;
      $this->derivatives['living_style_guide.guide.' . $entityType]['route_name'] = 'living_style_guide.guide.' . $entityType . '.' . $availableBundles[0];
      $this->derivatives['living_style_guide.guide.' . $entityType]['base_route'] = 'living_style_guide.guide';

      foreach ($availableBundles as $bundle) {
        $bundleLabel = $bundles[$entityType][$bundle]['label'];

        $this->derivatives['living_style_guide.guide.' . $entityType . '.' . $bundle] = $base_plugin_definition;
        $this->derivatives['living_style_guide.guide.' . $entityType . '.' . $bundle]['title'] = $bundleLabel;
        $this->derivatives['living_style_guide.guide.' . $entityType . '.' . $bundle]['route_name'] = 'living_style_guide.guide.' . $entityType . '.' . $bundle;
        $this->derivatives['living_style_guide.guide.' . $entityType . '.' . $bundle]['parent_id'] = 'living_style_guide.local_tasks:living_style_guide.guide.' . $entityType;
      }
    }

    return $this->derivatives;
  }

}
