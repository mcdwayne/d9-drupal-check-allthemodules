<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\field\formatter\EntityReferenceFormatterBase.
 */

namespace Drupal\entityreference\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;

use Drupal\field\Plugin\Type\Formatter\FormatterBase;

/**
 * Parent plugin for entity-reference formatters.
 */
abstract class EntityReferenceFormatterBase extends FormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::prepareView().
   *
   * Mark the accessible IDs a user can see. We do not unset unaccessible
   * values, as other may want to act on those values, even if they can
   * not be accessed.
   */
  public function prepareView(array $entities, $langcode, array &$items) {
    $target_ids = array();

    // Collect every possible entity attached to any of the entities.
    foreach ($entities as $id => $entity) {
      foreach ($items[$id] as $delta => $item) {
        if (isset($item['target_id'])) {
          $target_ids[] = $item['target_id'];
        }
      }
    }

    $target_type = $this->field['settings']['target_type'];

    if ($target_ids) {
      $target_entities = entity_load_multiple($target_type, $target_ids);
    }
    else {
      $target_entities = array();
    }

    // Iterate through the fieldable entities again to attach the loaded
    // data.
    foreach ($entities as $id => $entity) {

      foreach ($items[$id] as $delta => $item) {
        $items[$id][$delta]['entity'] = $target_entities[$item['target_id']];

        if (!isset($target_entities[$item['target_id']])) {
          continue;
        }

        $entity = $target_entities[$item['target_id']];

        // TODO: Improve when we have entity_access().
        $entity_access = $target_type == 'node' ? node_access('view', $entity) : TRUE;
        if (!$entity_access) {
          continue;
        }

        // Mark item as accessible.
        $items[$id][$delta]['access'] = TRUE;
      }
    }
  }

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   *
   * Remove unaccessible values.
   *
   * @see Drupal\entityreference\Plugin\field\formatter\EntityReferenceFormatterBase::prepareView().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    // Remove unaccessible items.
    foreach ($items as $delta => $item) {
      if (empty($item['access'])) {
        unset($items[$delta]);
      }
    }
    return array();
  }
}
