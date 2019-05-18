<?php

namespace Drupal\multiplechoice\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a node revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revision_link_delete")
 */
class ResultLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {


    return Url::fromRoute('multiplechoice.result_delete_confirm', ['result' => $node->id(),
      'node_revision' => $node->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete');
  }

}
