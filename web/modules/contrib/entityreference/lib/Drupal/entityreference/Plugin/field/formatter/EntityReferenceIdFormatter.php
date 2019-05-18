<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\field\formatter\EntityReferenceIdFormatter.
 */

namespace Drupal\entityreference\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityInterface;

use Drupal\entityreference\Plugin\field\formatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity-reference ID' formatter.
 *
 * @Plugin(
 *   id = "entityreference_entity_id",
 *   module = "entityreference",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("Display the ID of the referenced entities."),
 *   field_types = {
 *     "entityreference"
 *   }
 * )
 */
class EntityReferenceIdFormatter extends EntityReferenceFormatterBase {

  /**
   * Implements Drupal\field\Plugin\Type\Formatter\FormatterInterface::viewElements().
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => check_plain($item['target_id']));
    }

    return $elements;
  }

}
