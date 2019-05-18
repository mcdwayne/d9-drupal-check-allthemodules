<?php

namespace Drupal\moderation_note\Plugin\views\field;

use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a Moderation Note.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("moderation_note_link")
 */
class ModerationNoteLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    return $this->getEntity($row)->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View note');
  }

}
