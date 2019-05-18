<?php

namespace Drupal\google_scholar;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for adding Google Scholar meta tags to node output.
 */
interface TagRendererInterface {

  /**
   * Create the Google Scholar tags for a node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  The entity.
   *
   * @return
   *  An array of data for the tags. The keys are the keys for the 'html_head'
   *  array in the page attachments render array; the values are arrays suitable
   *  for the '#attributes' property, containing these properties:
   *    - 'name: The name of the meta tag.
   *    - 'content': The content of the meta tag.
   */
  public function buildTags(EntityInterface $entity);

}
