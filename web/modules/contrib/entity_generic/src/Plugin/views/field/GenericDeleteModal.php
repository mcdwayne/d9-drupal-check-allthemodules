<?php

namespace Drupal\entity_generic\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_generic_link_delete_modal")
 */
class GenericDeleteModal extends GenericOperationModalBase {

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo(ResultRow $row) {
    $url = Url::fromRoute('entity.' . $this->getEntityType() . '.delete_modal_form', [
      $this->getEntityType() => $this->getEntity($row)->id(),
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
