<?php

namespace Drupal\bibcite_entity\Plugin;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Link plugins.
 */
interface BibciteLinkPluginInterface extends PluginInspectionInterface {

  /**
   * Get plugin label.
   *
   * @return string
   *   Plugin label.
   */
  public function getLabel();

  /**
   * Build link using data from Reference entity.
   *
   * @param \Drupal\bibcite_entity\Entity\ReferenceInterface $reference
   *   Reference entity object.
   *
   * @return array
   *   Constructed URL render array or NULL if URL can not be constructed.
   */
  public function build(ReferenceInterface $reference);

}
