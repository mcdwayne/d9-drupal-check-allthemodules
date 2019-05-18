<?php

namespace Drupal\chat_channels\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Chat channel entities.
 */
class ChatChannelViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['chat_channel']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Chat channel'),
      'help' => $this->t('The Chat channel ID.'),
    ];

    return $data;
  }

}
