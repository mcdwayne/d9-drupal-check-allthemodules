<?php


namespace Drupal\chatroom\Controller;

use Drupal\chatroom\Entity\Chatroom;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for chatroom routes.
 */
class ChatroomController extends ControllerBase {

  /**
   * Posted messages are handled here.
   */
  public function postMessage(Chatroom $chatroom, Request $request) {
    $user = \Drupal::currentUser();

    // Content is expected to be json.
    $content = $request->getContent();
    $data = json_decode($content);
    if (empty($data->message)) {
      return new JsonResponse(array('data' => array('accessDenied' => 'invalid')));
    }

    $token = $request->headers->get('X-Csrf-Token');

    if (!$user->isAnonymous() && !chatroom_check_token($token, 'chatroom_form_token_' . $chatroom->cid->value)) {
      return new JsonResponse(array('data' => array('accessDenied' => 'token')));
    }

    $storage =  $this->entityManager()->getStorage('chatroom_message');

    $chatroom_message = $storage->create([
      'cid' => $chatroom->cid->value,
      'uid' => $user->id(),
      'text' => $data->message,
      'type' => chatroom_get_message_type($data->message),
      'anon_name' => isset($data->anonName) ? $data->anonName : '',
      'created' => REQUEST_TIME,
    ]);

    if ($chatroom_message->type->value == 'command') {
      $result = chatroom_call_command($chatroom_message, $chatroom);
      return new JsonResponse(array('data' => array('command' => $chatroom_message->type->value)));
    }
    else {
      $chatroom_message->save();

      $message_output = array(
        '#theme' => 'chatroom_message',
        '#chatroom_message' => $chatroom_message,
      );

      $push_message = (object) array(
        'channel' => 'chatroom_' . $chatroom->cid->value,
        'callback' => 'chatroomMessageHandler',
        'data' => array(
          'cid' => $chatroom->cid->value,
          'cmid' => $chatroom_message->cmid->value,
          'uid' => $user->id(),
          'timestamp' => $chatroom_message->created->value,
          'rendered' => drupal_render($message_output),
        ),
      );

      nodejs_send_content_channel_message($push_message);

      return new JsonResponse(array('data' => array('messageCmid' => $chatroom_message->cmid->value)));
    }
  }

  /**
   * Joins the user to a chatroom.
   * Sends the content token to be used by the client to join, and sends
   * the list of online users.
   */
  public function joinChat(Chatroom $chatroom) {
    $user = \Drupal::currentUser();
    $name = $user->getDisplayName();

    $username_render = [
      '#theme' => 'chatroom_user',
      '#account' => $user,
    ];

    $user_data = [
      'cid' => $chatroom->cid->value,
      'uid' => $user->id(),
      'rendered' => drupal_render($username_render),
      'name' => $name,
    ];

    // Generate token to add user to channel.
    $token_response = nodejs_send_content_channel_token('chatroom_' . $chatroom->cid->value, TRUE, $user_data);
    if (!$token_response) {
      $data = ['error' => 'Unable to generate channel token.'];
      return new JsonResponse($data, 500);
    }

    // Send the current list of users.
    $users = [];
    foreach ($chatroom->getOnlineUsers() as $chat_user) {
      $username_render = [
        '#theme' => 'chatroom_user',
        '#account' => $chat_user,
      ];

      $users[] = [
        'uid' => $chat_user->id(),
        'name' => $chat_user->getDisplayName(),
        'rendered' => drupal_render($username_render),
      ];
    }

    $data = [
      'channel' => 'chatroom_' . $chatroom->cid->value,
      'token' => $token_response->token,
      'users' => $users,
    ];

    return new JsonResponse($data);
  }

  /**
   * Retrieve previous messages for the given chatroom.
   */
  public function previousMessages(Chatroom $chatroom, Request $request) {
    $cmid = $request->query->get('cmid');
    $limit = $request->query->get('limit');

    if (!$limit) {
      $limit = 20;
    }

    $previous_messages = $chatroom->loadPreviousMessages($cmid, $limit);

    $message_data = [];
    foreach ($previous_messages as $previous_message) {
      $chatroom_message = [
        '#theme' => 'chatroom_message',
        '#chatroom' => $chatroom,
        '#chatroom_message' => $previous_message,
      ];

      $message_data[] = [
        'cid' => $previous_message->cid->entity->cid->value,
        'cmid' => $previous_message->cmid->value,
        'rendered' => drupal_render($chatroom_message),
      ];
    }

    return new JsonResponse($message_data);
  }

}
