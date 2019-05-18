<?php

namespace Drupal\chat_channels\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Chat channel member entities.
 */
class ChatChannelMemberViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['chat_channel_member']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Chat channel member'),
      'help' => $this->t('The Chat channel member ID.'),
    ];

    return $data;
  }

}
