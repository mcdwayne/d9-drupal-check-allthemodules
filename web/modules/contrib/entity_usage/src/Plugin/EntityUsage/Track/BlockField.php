<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\entity_usage\EntityUsageTrackBase;

/**
 * Tracks usage of entities related in block_field fields.
 *
 * @EntityUsageTrack(
 *   id = "block_field",
 *   label = @Translation("Block Field"),
 *   description = @Translation("Tracks relationships created with 'Block Field' fields."),
 *   field_types = {"block_field"},
 * )
 */
class BlockField extends EntityUsageTrackBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    /** @var \Drupal\block_field\BlockFieldItemInterface $item */
    $block_instance = $item->getBlock();
    if (!$block_instance) {
      return [];
    }

    $target_type = NULL;
    $target_id = NULL;

    // If there is a view inside this block, track the view entity instead.
    if ($block_instance->getBaseId() === 'views_block') {
      list($view_name, $display_id) = explode('-', $block_instance->getDerivativeId(), 2);
      // @todo worth trying to track the display id as well?
      // At this point the view is supposed to exist. Only track it if so.
      if ($this->entityTypeManager->getStorage('view')->load($view_name)) {
        $target_type = 'view';
        $target_id = $view_name;
      }
    }
    // @todo other special cases apart from views?
    else {
      $id = $block_instance->getConfiguration()['id'];
      if ($this->entityTypeManager->getStorage('block_content')->load($id)) {
        // Doing this here means that an initial save operation of a host entity
        // will likely not track this block, once it does not exist at this
        // point. However, it's preferable to miss that and ensure we only track
        // lodable entities.
        $target_type = 'block_content';
        $target_id = $block_instance->getConfiguration()['id'];
      }
    }

    return ($target_type && $target_id) ? [$target_type . '|' . $target_id] : [];
  }

}
