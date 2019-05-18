<?php

namespace Drupal\merci_line_item\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Merci Line Item entities.
 */
class MerciLineItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    $data['merci_line_item']['merci_line_item_bulk_form'] = array(
      'title' => $this->t('Merci operations bulk form'),
    'help' => $this->t('Add a form element that lets you run operations on multiple nodes.'),
    'field' => array(
      'id' => 'merci_line_item_bulk_form',
    ),
  );

    return $data;
  }

}
