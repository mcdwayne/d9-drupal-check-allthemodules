<?php

namespace Drupal\firebase\Service;

/**
 * Service for support code from previous module version.
 *
 * @deprecated
 */
class FirebaseNotificationService {

  /**
   * Firebase message service.
   *
   * @var \Drupal\firebase\Service\FirebaseMessageService
   */
  public $messageService;

  /**
   * FirebaseNotificationService constructor.
   *
   * @param \Drupal\firebase\Service\FirebaseMessageService $messageService
   *   The message service for sending message to FCM.
   */
  public function __construct(FirebaseMessageService $messageService) {
    $this->messageService = $messageService;
  }

  /**
   * Prepare to send notification.
   *
   * @param string|array $token
   *   The device token.
   * @param array $param
   *   Params for building message to FCM.
   */
  public function send($token, array $param) {
    $options = [];
    $notification = [];

    // Collect all data to arrays for sending him to new message service.
    if (!isset($param['priority'])) {
      $options['priority'] = 'high';
    }
    if (isset($param['icon'])) {
      $notification['icon'] = $param['icon'];
    }
    if (isset($param['sound'])) {
      $notification['sound'] = $param['sound'];
    }
    if (isset($param['click_action'])) {
      $notification['click_action'] = $param['click_action'];
    }
    if (isset($param['badge'])) {
      $notification['badge'] = $param['badge'];
    }
    if (isset($param['content_available'])) {
      $options['content_available'] = $param['content_available'];
    }
    if (!empty($param['title']) && !empty($param['body'])) {
      $notification += [
        'title' => $param['title'],
        'body' => $param['body'],
      ];
    }

    $this->messageService->setRecipients($token);
    $this->messageService->setOptions($options);
    $this->messageService->setNotification($notification);
    if (isset($param['data'])) {
      $this->messageService->setData($param['data']);
    }
    $this->messageService->send();
  }

}
