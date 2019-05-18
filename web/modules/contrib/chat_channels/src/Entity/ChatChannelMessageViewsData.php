<?php

namespace Drupal\chat_channels\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Chat channel message entities.
 */
class ChatChannelMessageViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['chat_channel_message']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Chat channel message'),
      'help' => $this->t('The Chat channel message ID.'),
    );

    return $data;
  }

}
