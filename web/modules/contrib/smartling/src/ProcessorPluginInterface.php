<?php

/**
 * @file
 * Contains \Drupal\smartling\ProcessorPluginInterface.
 */

namespace Drupal\smartling;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for source plugin controllers.
 */
interface ProcessorPluginInterface extends PluginInspectionInterface {

  /**
   * Update entity from given xml string.
   *
   * @param string $xml_content
   *   XML ready to be parsed that contains translated entity.
   */
  public function updateEntity($xml_content);

  /**
   * Build xml string
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $smartling_entity
   *   Smartling entit.
   *
   * @return \DOMDocument Returns XML object.
   *   Returns XML object.
   */
  public function exportContentToXML(SmartlingSubmissionInterface $smartling_entity);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return array
   *   Content entity exported to array.
   */
  public function exportContentToArray(ContentEntityInterface $entity);

}
