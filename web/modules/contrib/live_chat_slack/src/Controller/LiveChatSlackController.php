<?php
/**
 * @file
 * Contains \Drupal\live_chat_slack\Controller\LiveChatSlackController.
 */

namespace Drupal\live_chat_slack\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\live_chat_slack\SlackService;

class LiveChatSlackController extends ControllerBase {

  /**
   * @var \Drupal\live_chat_slack\SlackService
   */
  protected $slackService;

  /**
   * {@inheritdoc}
   */
  public function __construct(SlackService $slackService) {
    $this->slackService = $slackService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('live_chat_slack.slack_service')
    );
  }

  public function check_if_user_is_online() {
    return new JsonResponse($this->slackService->userIsOnline(), 200, ['Content-Type'=> 'application/json']);
  }

  public function send_message(Request $request) {
    $content = $request->getContent();
    $result = $this->slackService->sendMessage($content);
    $message = $this->renderMessage($result);
    return new JsonResponse($message, 200, ['Content-Type'=> 'application/json']);
  }

  public function get_history() {
    $history = $this->slackService->getGroupHistory();
    $historyArray = $this->renderMessage($history);
    return new JsonResponse($historyArray , 200, ['Content-Type'=> 'application/json']);
  }

  private function renderMessage($slackResultObject) {
    $messageArray = [];
    if($slackResultObject['ok']) {
      if(isset($slackResultObject['message'])) {
        if($slackResultObject['message']['type'] == 'message' && $slackResultObject['message']['subtype'] != 'group_join') {
          $element = [
            '#theme' => 'live_chat_slack_block_msg',
            '#sender' => empty($slackResultObject['message']['username']) ? $this->slackService->getUsername() : $slackResultObject['message']['username'],
            '#user_image' => drupal_get_path('module', 'live_chat_slack') . '/assets/img/user-icon.png',
            '#msg' => $slackResultObject['message']['text'],
            '#ts' => date("H:i",$slackResultObject['ts']),
          ];
          $messageArray[] = \Drupal::service('renderer')->render($element);
        }
      } elseif (isset($slackResultObject['messages'])) {
        foreach($slackResultObject['messages'] as $message) {
          if($message['type'] == 'message' && $message['subtype'] != 'group_join') {
            $element = [
              '#theme' => 'live_chat_slack_block_msg',
              '#sender' => empty($message['username']) ? $this->slackService->getUsername() : $message['username'],
              '#user_image' => (empty($message['username'])) ? $this->slackService->getUserImage() : drupal_get_path('module', 'live_chat_slack') . '/assets/img/user-icon.png',
              '#msg' => $message['text'],
              '#ts' => date("H:i",$message['ts']),
            ];
            $messageArray[] = \Drupal::service('renderer')->render($element);
          }
        }
      }

    }
    return $messageArray;
  }
}