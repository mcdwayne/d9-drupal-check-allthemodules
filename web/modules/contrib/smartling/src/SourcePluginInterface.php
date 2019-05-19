<?php

/**
 * @file
 * Contains \Drupal\smartling\SourcePluginInterface.
 */

namespace Drupal\smartling;

use Drupal\Component\Plugin\PluginInspectionInterface;
use \Drupal\Core\Entity\EntityInterface;

/**
 * Interface for source plugin controllers.
 */
interface SourcePluginInterface extends PluginInspectionInterface {

  /**
   * Returns an array with the data structured for translation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The job item entity.
   *
   * @see JobItem::getData()
   */
  public function getTranslatableXML(EntityInterface $entity);

  /**
   * Saves a translation.
   *
   * @param string $xml
   *   XML file as a single string
   * @param \Drupal\smartling\SmartlingSubmissionInterface $submission
   *   The smartling item entity.
   *
   * @return boolean
   *   TRUE if the translation was saved successfully, FALSE otherwise.
   */
  public function saveTranslation($xml, SmartlingSubmissionInterface $submission);

  /**
   * Return a title for this job item.
   *
   * @param \Drupal\smartling\SmartlingSubmissionInterface $smartling_item
   *   The smartling item entity.
   */
  public function getLabel(SmartlingSubmissionInterface $smartling_item);
}
