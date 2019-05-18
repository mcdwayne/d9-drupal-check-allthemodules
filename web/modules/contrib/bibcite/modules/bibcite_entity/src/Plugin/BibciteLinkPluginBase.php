<?php

namespace Drupal\bibcite_entity\Plugin;

use Drupal\bibcite_entity\Entity\ReferenceInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Link;

/**
 * Base class for Link plugins.
 */
abstract class BibciteLinkPluginBase extends PluginBase implements BibciteLinkPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Build URL object.
   *
   * @param \Drupal\bibcite_entity\Entity\ReferenceInterface $reference
   *   Reference entity object.
   */
  protected function buildUrl(ReferenceInterface $reference) {}

  /**
   * {@inheritdoc}
   */
  public function build(ReferenceInterface $reference) {
    if ($url = $this->buildUrl($reference)) {
      return Link::fromTextAndUrl($this->getLabel(), $this->buildUrl($reference))->toRenderable();
    }

    return NULL;
  }

}
