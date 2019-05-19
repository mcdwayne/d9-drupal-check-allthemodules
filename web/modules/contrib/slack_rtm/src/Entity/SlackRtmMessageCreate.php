<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 3/12/18
 * Time: 9:53 AM
 */

namespace Drupal\slack_rtm\Entity;


use Drupal\slack_rtm\SlackRtmApi;
use Drupal\slack_rtm\Entity\SlackRtmMessage;

class SlackRtmMessageCreate extends SlackRtmApi {

  /**
   * The batch array.
   *
   * @var array
   */
  protected $batch;

  /**
   * SlackRtmMessageCreate constructor.
   *
   * @param array $batch
   */
  public function __construct(array $batch) {
    $this->batch = $batch;
  }

  /**
   * Generates the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  public function generateEntity() {
    // Set up the vars to use.
    $message = $this->batch['message'];
    $users = $this->batch['users'];
    $id = $this->batch['id'];
    if (isset($this->batch['channels_list'])) {
      $channels_list = $this->batch['channels_list'];
      $channel = $channels_list[$id];
    }
    else {
      $channel = $this->batch['id'];
    }

    // Grab the user.
    $message['author'] = @$users[$message['user']] ?: NULL;

    // If no user, the leave.
    if ($message['author'] === NULL) {
      return NULL;
    }

    // Grab the perma-link for the message.
    $params = [
      'channel' => $id,
      'message_ts' => $message['ts'],
      'token' => $this->getToken('slack_bot_token'),
    ];
    $perma_link = $this->callApi('chat.getPermalink', $params);

    // Easier to send this all as one $message var.
    $link = @$perma_link["permalink"] ?: NULL;

    // Replace the user name that is directly pinged in the message.
    if (strpos($message['text'], '<@') !== FALSE) {
      preg_match('/<@(.*?)>/', $message['text'], $text);
      $t = 1;
      if (isset($text[1])) {
        $replace = $users[$text[1]];
        $message['text'] = str_replace($text[1], $replace, $message['text']);
      }
    }

    $result = SlackRtmMessage::create();
    $result->set('name', $channel . $message['ts']);
    $result->set('channel', $channel);
    $result->set('message', $message['text']);
    $result->set('permalink', $link);
    $result->set('message_author', $message['author']);
    $result->set('created', strtok($message['ts'], '.'));
    $result->set('tid', $message['ts']);
    $result->save();

    return $result;

  }
}