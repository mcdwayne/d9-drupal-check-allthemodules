<?php

namespace Drupal\box\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a box revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("box_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = $this->getEntity($row);
    // Current revision uses the box view path.
    return !$box->isDefaultRevision() ?
      Url::fromRoute('entity.box.revision', ['box' => $box->id(), 'box_revision' => $box->getRevisionId()]) :
      $box->urlInfo();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = $this->getEntity($row);
    if (!$box->getRevisionid()) {
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
