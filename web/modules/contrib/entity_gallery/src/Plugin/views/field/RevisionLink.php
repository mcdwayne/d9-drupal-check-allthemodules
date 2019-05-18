<?php

namespace Drupal\entity_gallery\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to an entity gallery revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_gallery_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $this->getEntity($row);
    // Current revision uses the entity gallery view path.
    return !$entity_gallery->isDefaultRevision() ?
      Url::fromRoute('entity.entity_gallery.revision', ['entity_gallery' => $entity_gallery->id(), 'entity_gallery_revision' => $entity_gallery->getRevisionId()]) :
      $entity_gallery->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $this->getEntity($row);
    if (!$entity_gallery->getRevisionid()) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View');
  }

}
