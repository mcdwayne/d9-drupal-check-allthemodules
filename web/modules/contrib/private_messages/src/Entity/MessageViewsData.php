<?php

namespace Drupal\private_messages\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Message entities.
 */
class MessageViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

//    $data['message']['table']['base'] = array(
//      'field' => 'id',
//      'title' => $this->t('Message'),
//      'help' => $this->t('The Message ID.'),
//    );

    return $data;
  }

}
