<?php

namespace Drupal\pwa_firebase_notification\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the pwa module.
 */
class NotificationController extends ControllerBase {

  /**
   * Returns the firebase service worker and add's the necessary libaries and dynamic variables.
   */
  public function getFirebaseSw() {
    return new Response(
      '
        importScripts("https://www.gstatic.com/firebasejs/4.8.1/firebase-app.js");
        importScripts("https://www.gstatic.com/firebasejs/4.8.1/firebase-messaging.js");
        firebase.initializeApp({\'messagingSenderId\': \''
      . \Drupal::config('firebase.settings')->get('sender_id')
      . '\'});
        const messaging = firebase.messaging();',
      200,
      [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
      ]
    );
  }

  /**
   * Function receives the user token and save's it in the configurations.
   */
  public function tokenReceived($token) {
    $tokens = \Drupal::state()->get('pwa_firebase_notification.tokens');

    // Get tokens array.
    if (!in_array($token, $tokens)) {
      $tokens[] = $token;
    }

    // Save tokens.
    \Drupal::state()->set('pwa_firebase_notification.tokens', $tokens);

    return new JsonResponse(['data' => 'success']);
  }

  /**
   * Function to send a notification to all the users.
   *
   * @param string $title
   *   Message title.
   * @param string $message
   *   Message body.
   * @param string $url
   *   Message body.
   */
  public static function sendMessageToAllUsers($title, $message, $url = NULL) {
    if (strlen($title) > 100) {
      $title = substr($title, 0, 97) . '...';
    }
    if (strlen($message) > 255) {
      $message = substr($message, 0, 252) . '...';
    }
    if (empty($url)) {
      $url = "https://" . $_SERVER['HTTP_HOST'];
    }

    $config = \Drupal::config('pwa.config');
    $tokens = \Drupal::state()->get('pwa_firebase_notification.tokens');

    // Note: this is the image that is used for the manifest file.
    // See issue: https://www.drupal.org/project/pwa/issues/2954461 for this patch.
    $image = !empty($config->get('image')) ? 'https://' . $_SERVER['HTTP_HOST'] . $config->get('image') : 'optional-image';

    foreach ($tokens as $i => $token) {
      try {
        $messageService = \Drupal::service('firebase.message');
        $messageService->setRecipients($token);
        $messageService->setNotification([
          'title' => $title,
          'body' => $message,
          'badge' => 1,
          'icon' => $image,
          'sound' => 'optional-sound',
          'click_action' => $url,
        ]);
        $messageService->setData([
          'score' => '3x1',
          'date' => date('Y-m-d'),
          'optional' => 'Data is used to send silent pushes. Otherwise, optional.',
        ]);
        $messageService->setOptions(['priority' => 'normal']);
        $result = $messageService->send();

        if (empty($result) || !empty($result['failure'])) {
          unset($tokens[$i]);
        }

      }
      catch (\Exception $exception) {
        \Drupal::logger('pwa')->error($exception->getMessage());
      }
    }

    // Update tokens.
    \Drupal::state()->set('pwa_firebase_notification.tokens', $tokens);
  }

}
