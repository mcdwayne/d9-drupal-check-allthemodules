<?php

namespace Drupal\tmgmt_memory\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a confirmation delete form for 'tmgmt_memory_segment_translation' entity.
 */
class SegmentTranslationDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return Url::fromRoute('view.tmgmt_memory.page_1');
  }

}
