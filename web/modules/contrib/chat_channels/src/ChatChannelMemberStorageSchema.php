<?php

/**
 * @file
 * Contains \Drupal\chat_channels\ChatChannelMemberStorageSchema.
 */

namespace Drupal\redirect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the redirect schema.
 */
class ChatChannelMemberStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['chat_channel_member']['indexes'] += [
      'channel_user' => ['channel', 'uid'],
    ];

    return $schema;
  }

}
