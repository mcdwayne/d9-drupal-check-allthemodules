<?php

namespace Drupal\simple_modal_entity_form\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("modal_entity_delete_field")
 */
class ModalEntityDeleteField extends ModalEntityOperationBase {

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo(ResultRow $row) {
    $url = Url::fromRoute('modal_entity_form.delete', [
      'entity_type' => $this->getEntityType(),
      'entity' => $this->getEntity($row)->id(),
    ]);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('delete');
  }

}
