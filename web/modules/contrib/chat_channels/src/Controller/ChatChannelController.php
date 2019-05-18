<?php

namespace Drupal\chat_channels\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\chat_channels\ChatChannelManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\chat_channels\Entity\ChatChannelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\chat_channels\Form\ChatChannelChatForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ChatChannelController
 *
 * @package Drupal\chat_channels\Controller
 */
class ChatChannelController implements ContainerInjectionInterface {

  /**
   * The entity manager
   *
   * @var \Drupal\chat_channels\ChatChannelManagerInterface
   */
  protected $chatChannelManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\ContentEntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $chatChannelStorage;

  /**
   * Creates an ChatChannelController object.
   *
   * @param \Drupal\chat_channels\ChatChannelManagerInterface $chat_channel_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(ChatChannelManagerInterface $chat_channel_manager, RendererInterface $renderer, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->chatChannelManager = $chat_channel_manager;
    $this->renderer = $renderer;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\chat_channels\ChatChannelManagerInterface $chat_channel_manager */
    $chat_channel_manager = $container->get('chat_channel.manager');

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $container->get('renderer');

    /** @var \Drupal\Core\Session\AccountInterface $current_user */
    $current_user = $container->get('current_user');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $chat_channel_manager,
      $renderer,
      $current_user,
      $entity_type_manager
    );
  }

  public function title(ChatChannelInterface $chat_channel) {
    return $chat_channel->getName();
  }

  /**
   * Load a chat page.
   *
   * @param \Drupal\chat_channels\Entity\ChatChannelInterface $chat_channel
   *
   * @return array
   */
  public function channel(ChatChannelInterface $chat_channel) {
    /** @var \Drupal\chat_channels\Entity\ChatChannelMemberInterface $member */
    if (!$member = $this->chatChannelManager->getMember($chat_channel, $this->currentUser)) {
      $member = $this->chatChannelManager->joinChannel($chat_channel, $this->currentUser);
    }

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface $lastSeenMessage */
    $lastSeenMessage = $this->chatChannelManager->getLastSeenMessage($chat_channel, $this->currentUser);

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface[] $messages */
    $messages = $this->chatChannelManager->getMessages($chat_channel, FALSE, 'ASC');
    $messages_render_array = $this->chatChannelManager->buildMessages($messages, $lastSeenMessage);

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface $firstMessageToday */
    $firstMessageToday = $this->chatChannelManager->getFirstMessageToday($chat_channel);

    // Get a render array of a message indicator, indicating the number of new
    // messages since last time.
    $newMessageIndicator = $this->chatChannelManager->getNewMessageIndicator($member);

    $render_array = [];

    $firstNewMessage = FALSE;
    foreach ($messages_render_array as $key => $render_element) {
      if (!empty($firstMessageToday) && $key == $firstMessageToday->id()) {
        $render_array[] = [
          '#theme' => 'chat_channel_message_divider',
          '#label' => t('Today'),
        ];
      }
      if (!empty($lastSeenMessage) && $key > $lastSeenMessage->id() && !$firstNewMessage) {
        $firstNewMessage = TRUE;
        $render_array[] = [
          '#theme' => 'chat_channel_message_divider',
          '#label' => t('New messages'),
        ];
      }
      $render_array[] = $render_element;
    }

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface $newLastSeenMessage */
    if (!empty($messages)) {
      $newLastSeenMessage = end($messages);
      $member->setLastSeenMessageId($newLastSeenMessage->id());
      $member->save();
    }

    $form = new ChatChannelChatForm($chat_channel);

    return [
      '#theme' => 'chat_channel',
      '#chat_channel' => $chat_channel,
      'messages' => $render_array,
      'new_messages_indicator' => $newMessageIndicator,
      'chat_input_form' => \Drupal::formBuilder()->getForm($form),
    ];
  }

  /**
   *
   * Ajax callback to get new messages for a channel.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function refreshChannel(Request $request) {
    // Get the last message id and channel from the request.
    /** @var \Symfony\Component\HttpFoundation\ParameterBag $requestParameters */
    $requestParameters = $request->request;
    $channelId = $requestParameters->get('channelid');
    $chatChannel = $this->chatChannelManager->getChannel($channelId);
    $member = $this->chatChannelManager->getMember($chatChannel, $this->currentUser);

    // Get new messages.
    $lastSeenMessage = $this->chatChannelManager->getLastSeenMessage($chatChannel, $this->currentUser);
    $new_messages = $this->chatChannelManager->getNewMessages($member);
    $messages_render_array = $this->chatChannelManager->buildMessages($new_messages, $lastSeenMessage);

    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface $newLastSeenMessage */
    $newLastSeenMessage = end($new_messages);

    if ($newLastSeenMessage) {
      $member->setLastSeenMessageId($newLastSeenMessage->id());
      $member->save();
    }

    // Render the messages in a string.
    $ouput = $this->renderer->render($messages_render_array);

    $return = [
      'messages' => $ouput,
      'message_count' => count($new_messages),
    ];

    return new JsonResponse($return);
  }
}
