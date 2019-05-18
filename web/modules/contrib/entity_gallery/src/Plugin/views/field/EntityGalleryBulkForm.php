<?php

namespace Drupal\entity_gallery\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines an entity gallery operations bulk form element.
 *
 * @ViewsField("entity_gallery_bulk_form")
 */
class EntityGalleryBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No entity galleries selected.');
  }

}
