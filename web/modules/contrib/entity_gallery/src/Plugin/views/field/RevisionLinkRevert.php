<?php

namespace Drupal\entity_gallery\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to revert an entity gallery to a revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_gallery_revision_link_revert")
 */
class RevisionLinkRevert extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $this->getEntity($row);
    return Url::fromRoute('entity_gallery.revision_revert_confirm', ['entity_gallery' => $entity_gallery->id(), 'entity_gallery_revision' => $entity_gallery->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Revert');
  }
}
