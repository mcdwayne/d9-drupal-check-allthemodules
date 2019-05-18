<?php

namespace Drupal\chat_channels;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Chat channel member entities.
 *
 * @ingroup chat_channels
 */
class ChatChannelMemberListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Chat channel member ID');
    $header['name'] = $this->t('Username');
    $header['channel'] = $this->t('Channel name');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\chat_channels\Entity\ChatChannelMemberInterface $entity */
    $row['id'] = $entity->id();

    /** @var \Drupal\user\UserStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('user');

    /** @var \Drupal\user\UserInterface $user */
    $user = $storage->load($entity->getUserId());

    $row['name'] = $this->l(
      $user->getAccountName(),
      new Url(
        'entity.user.canonical', [
          'user' => $entity->getUserId(),
        ]
      )
    );

    /** @var  \Drupal\Core\Entity\ContentEntityStorageInterface $channel_storage */
    $channel_storage = \Drupal::entityTypeManager()->getStorage('chat_channel');

    /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $channel */
    $channel = $channel_storage->load($entity->getChannelId());

    $row['channel'] = !empty($channel) ? $channel->getName() : '';

    return $row + parent::buildRow($entity);
  }

}
