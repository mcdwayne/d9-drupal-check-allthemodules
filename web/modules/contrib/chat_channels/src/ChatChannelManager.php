<?php
namespace Drupal\chat_channels;

use Drupal\chat_channels\Entity\ChatChannelInterface;
use Drupal\chat_channels\Entity\ChatChannelMemberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChatChannelManager implements ChatChannelManagerInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $channelStorage;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $channelMessageStorage;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $channelMemberStorage;

  /**
   * Constructs a new ChatChannelManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\Query\QueryInterface $entityQuery
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryInterface $entityQuery) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager */
    $this->entityTypeManager = $entity_type_manager;

    /** @var \Drupal\Core\Entity\EntityStorageInterface channelStorage */
    $this->channelStorage = $this->entityTypeManager
      ->getStorage('chat_channel');

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface channelMessageStorage */
    $this->channelMessageStorage = $this->entityTypeManager
      ->getStorage('chat_channel_message');

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface channelMemberStorage */
    $this->channelMemberStorage = $this->entityTypeManager
      ->getStorage('chat_channel_member');
  }

  public function create(ContainerInterface $container) {

    return new static(

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestMessages(ChatChannelInterface $channel, $limit = 20, $sort = 'DESC') {
    $messages = $this->getMessages($channel, $limit, $sort);

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(ChatChannelInterface $channel, $limit = FALSE, $sort = 'DESC') {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('chat_channel_message')
      ->condition('channel', $channel->id())
      ->sort('created', $sort);

    if ($limit) {
      $query->range(0, $limit);
    }

    $message_ids = $query->execute();

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface[] $messages */
    $messages = $this->channelMessageStorage->loadMultiple($message_ids);

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getMember(ChatChannelInterface $channel, AccountInterface $user) {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('chat_channel_member')
      ->condition('channel', $channel->id())
      ->condition('uid', $user->id());

    $member_id = $query->execute();

    /** @var \Drupal\chat_channels\Entity\ChatChannelMemberInterface $member */
    $member = $this->channelMemberStorage->load(array_shift($member_id));

    return $member;
  }

  /**
   * {@inheritdoc}
   */
  public function joinChannel(ChatChannelInterface $channel, AccountInterface $user) {
    // Create the membership to the channel for the user.
    $member = $this->channelMemberStorage->create([
      'uid' => $user->id(),
      'channel' => $channel->id(),
      'last_seen_message' => 0,
      'member_since' => REQUEST_TIME,
    ]);

    $member->save();

    return $member;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewMessages(ChatChannelMemberInterface $member, $count = FALSE) {
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('chat_channel_message')
      ->condition('channel', $member->getChannelId())
      ->condition('eid', $member->getLastSeenMessageId(), '>');

    $message_ids = $query->execute();

    if ($count) {
      return count($message_ids);
    }

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface[] $messages */
    $messages = $this->channelMessageStorage->loadMultiple($message_ids);

    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountNewMessages(ChatChannelMemberInterface $member) {
    return $this->getNewMessages($member, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getNewMessageIndicator(ChatChannelMemberInterface $member) {
    $count = $this->getCountNewMessages($member);

    $render_array = [
      '#theme' => 'chat_channel_new_message_indicator',
      '#count' => $count ? $count : '',
      '#cache' => [
        'tags' => $member->getCacheTags()
      ],
    ];

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrappedNewMessageIndicator(ChatChannelMemberInterface $member, $elements) {
    $newMessageIndicator = $this->getNewMessageIndicator($member);

    $default_elements = [
      'indicator' => $newMessageIndicator,
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'class' => [
              'indicator-wrap'
            ]
          ]
        ]
      ],
    ];

    $render_array = array_merge($elements, $default_elements);

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMessages($messages, $lastSeenMessage = NULL) {
    if(!empty($messages)) {
      /** @var \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder */
      $view_builder = $this->entityTypeManager
        ->getViewBuilder('chat_channel_message');

      $render_array = [];
      foreach($messages as $message) {
        $message_render_array = $view_builder->view($message);
        if(isset($lastSeenMessage) && $message->id() > $lastSeenMessage->id()) {
          $message_render_array['#new_message'] = TRUE;
        }
        $render_array[$message->id()] = $message_render_array;
      }

      return $render_array;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstMessageToday(ChatChannelInterface $chat_channel) {
    $timestamp = strtotime('today midnight');
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = \Drupal::entityQuery('chat_channel_message')
      ->condition('channel', $chat_channel->id())
      ->condition('created', $timestamp, '>')
      ->range(0, 1);

    $message_id = $query->execute();

    $messages = [];
    if (!empty($message_id)) {
      /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface[] $messages */
      $messages = $this->channelMessageStorage->loadMultiple($message_id);
    }
    return array_shift($messages);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSeenMessage(ChatChannelInterface $chat_channel, AccountInterface $user) {
    $member = $this->getMember($chat_channel, $user);

    if ($member) {
      /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface[] $messages */
      $message = $this->channelMessageStorage->load(
        $member->getLastSeenMessageId()
      );

      return $message;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannel($channelId) {
      return $this->channelStorage->load($channelId);
  }
}