<?php

namespace Drupal\item_lot;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the item_lot edit forms.
 */
class ItemLotForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $item_lot = $this->entity;
    $insert = $item_lot->isNew();
    $item_lot->save();
    $item_lot_link = $item_lot->link($this->t('View'));
    $context = ['%title' => $item_lot->label(), 'link' => $item_lot_link];
    $t_args = ['%title' => $item_lot->link($item_lot->label())];

    if ($insert) {
      $this->logger('item_lot')->notice('ItemLot: added %title.', $context);
      drupal_set_message($this->t('ItemLot %title has been created.', $t_args));
    }
    else {
      $this->logger('item_lot')->notice('ItemLot: updated %title.', $context);
      drupal_set_message($this->t('ItemLot %title has been updated.', $t_args));
    }
  }

}
