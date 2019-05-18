<?php

namespace Drupal\entity_collector\Plugin\PanelizerEntity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\panelizer\Plugin\PanelizerEntityBase;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Panelizer entity plugin for integrating with entity collections..
 *
 * @PanelizerEntity("entity_collection")
 */
class PanelizerEntityCollection extends PanelizerEntityBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultDisplay(EntityViewDisplayInterface $display, $bundle, $view_mode) {
    $panels_display = parent::getDefaultDisplay($display, $bundle, $view_mode)
      ->setPageTitle('[entity_collection:name]');

    // Remove the 'name' block because it's covered already.
    foreach ($panels_display->getRegionAssignments() as $region => $blocks) {
      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_id => $block) {
        if ($block->getPluginId() == 'entity_field:entity_collection:name') {
          $panels_display->removeBlock($block_id);
        }
      }
    }

    return $panels_display;
  }

  /**
   * {@inheritdoc}
   */
  public function alterBuild(array &$build, EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode) {
    /** @var $entity \Drupal\media\Entity\Media */
    parent::alterBuild($build, $entity, $panels_display, $view_mode);

    if ($entity->id()) {
      $build['#contextual_links']['entity_collection'] = [
        'route_parameters' => ['entity_collection' => $entity->id()],
        'metadata' => ['changed' => $entity->getChangedTime()],
      ];
    }
  }

}
