<?php

namespace Drupal\chat_channels;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Chat channel message entities.
 *
 * @ingroup chat_channels
 */
class ChatChannelMessageListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Chat channel message ID');
    $header['user'] = $this->t('User name');
    $header['channel'] = $this->t('Channel name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\chat_channels\Entity\ChatChannelMessage */
    $row['id'] = $entity->id();

    /** @var \Drupal\user\UserStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('user');

    /** @var \Drupal\user\UserInterface $user */
    $user = $storage->load($entity->getOwnerId());

    $row['name'] = $this->l(
      $user->getAccountName(),
      new Url(
        'entity.user.canonical', [
          'user' => $entity->getOwnerId(),
        ]
      )
    );

    /** @var  \Drupal\Core\Entity\ContentEntityStorageInterface */
    $channel_storage = \Drupal::entityTypeManager()->getStorage('chat_channel');

    /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $channel */
    $channel = $channel_storage->load($entity->getChannelId());

    $row['channel'] = !empty($channel) ? $channel->getName() : '';
    return $row + parent::buildRow($entity);
  }

}
