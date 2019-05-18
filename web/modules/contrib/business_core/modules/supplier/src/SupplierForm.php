<?php

namespace Drupal\supplier;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the supplier edit forms.
 */
class SupplierForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $supplier = $this->entity;
    $insert = $supplier->isNew();
    $supplier->save();
    $supplier_link = $supplier->link($this->t('View'));
    $context = ['%title' => $supplier->label(), 'link' => $supplier_link];
    $t_args = ['%title' => $supplier->link($supplier->label())];

    if ($insert) {
      $this->logger('supplier')->notice('Supplier: added %title.', $context);
      drupal_set_message($this->t('Supplier %title has been created.', $t_args));
    }
    else {
      $this->logger('supplier')->notice('Supplier: updated %title.', $context);
      drupal_set_message($this->t('Supplier %title has been updated.', $t_args));
    }
  }

}
