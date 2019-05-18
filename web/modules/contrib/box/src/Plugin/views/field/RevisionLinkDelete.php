<?php

namespace Drupal\box\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a box revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("box_revision_link_delete")
 */
class RevisionLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = $this->getEntity($row);
    return Url::fromRoute('box.revision_delete_confirm', ['box' => $box->id(), 'box_revision' => $box->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete');
  }

}
