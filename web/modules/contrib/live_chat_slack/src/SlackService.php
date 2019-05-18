<?php

/**
 * @file
 * Contains \Drupal\live_chat_slack\SlackService.
 */

namespace Drupal\live_chat_slack;

use \Drupal\user\PrivateTempStoreFactory;
use \Drupal\Core\Session\SessionManagerInterface;
use \Drupal\Core\Session\AccountInterface;

class SlackService {

  protected $slack;
  protected $userName;
  protected $userId;
  protected $userImage;
  protected $groupId;
  protected $tempStoreFactory;
  protected $sessionManager;
  protected $currentUser;
  protected $store;

  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct() {
    $this->updateSettings();
  }

  public function updateSettings() {
    $config = \Drupal::config('block.block.livechatslack');
    $this->slack = new Slack($config->get('settings.live_chat_slack_block_api_token'));
    $this->userName = (!empty($config->get('settings.live_chat_slack_block_user_name'))) ? $config->get('settings.live_chat_slack_block_user_name') : '';
    $this->userId = (!empty($config->get('settings.live_chat_slack_block_user_id'))) ? $config->get('settings.live_chat_slack_block_user_id') : '';
    $this->userImage = (!empty($config->get('settings.live_chat_slack_block_user_image'))) ? $config->get('settings.live_chat_slack_block_user_image') : '';
    if($this->doesGroupExist()) {
      $this->groupId = $this->getGroupId();
    }
  }

  public function userIsOnline() {
    if($this->slack) {
      $presence = $this->slack->call('users.getPresence', array('user' => $this->userId));
      if ($presence['ok'] == 1) {
        return ['status' => TRUE];
      }
      else {
        return ['status' => FALSE];
      }
    }
  }

  public function sendMessage($txt) {
    if($this->slack) {
      if(!$this->doesGroupExist()) {
        $this->createGroup(date("d_m_Y__H_i",time()));
      }

      return $this->slack->call('chat.postMessage', array(
        'channel' => $this->getGroupId(),
        'text' => $txt,
        'username' => t('You'),
        'as_user' => FALSE,
      ));
    }
  }

  private function getGroupId() {
    return $_SESSION['live_chat_slack']['group_id'];
  }

  private function doesGroupExist() {
    return !empty($_SESSION['live_chat_slack']['group_id']);
  }

  private function createGroup($name) {
    if($this->slack && !$this->doesGroupExist()) {
      $obj = $this->slack->call('groups.create', array(
        'name' => $name,
      ));
      if($obj['ok']) {
        $this->groupId = $obj['group']['id'];
        $_SESSION['live_chat_slack']['group_id'] = $obj['group']['id'];
      }
      return $obj;

    }
  }

  public function getGroupHistory() {
    if($this->slack) {
      if(!$this->doesGroupExist()) {
        return array();
      }

      return $this->slack->call('groups.history', array(
        'channel' => $this->getGroupId(),
      ));
    }
  }

  private function getUserProfileImageFromSlack() {
    $slackUserList = $this->slack->call('users.list');
    if(!empty($slackUserList)) {
      $found = false;
      foreach($slackUserList['members'] as $key => $slackUser) {
        if($slackUser['name'] == $this->getUsername()) {
          $this->userImage = $slackUser['profile']['image_32'];
        }
      }
    }
  }

  public function getUsername() {
    return $this->userName;
  }

  private function getUserId() {
    return $this->userId;
  }

  public function getUserImage() {
    return $this->userImage;
  }
}