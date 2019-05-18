<?php
/**
 * @file
 * Contains \Drupal\example\Controller\ExamleController.
 */

namespace Drupal\ricochet_maintenance_helper\Controller;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Access\AccessResult;


/**
 * Creates a callback for listening to the server.
 */
class ListenerPageController {

  /**
   * Check for page access. Only if listening is set to true.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(AccountInterface $account) {
    $config = \Drupal::config('ricochet_maintenance_helper.admin_config');
    $listen = $config->get('listen');
    return ($listen) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  public function content() {
    $received = json_decode($_POST['data'], TRUE);
    $updateHelper = \Drupal::service('ricochet_maintenance_helper.update_helper');
    // No key equals error response.
    if (!isset($received['key'])) {
      $severity = RMH_STATUS_ERROR;
      $message = 'Received an invalid request in listening mode.';
      $updateHelper->writeStatus($severity, $message, $output = FALSE);
      return $this->jsonResponse('Malformed data.', 'error');
    }
    $key = $received['key'];
    $result = $updateHelper->testUpdate($key);
    if ($result == TRUE) {
      $this->jsonResponse('Success.', 'status');
    }
    else {
      $this->jsonResponse('Unknown error.', 'error');
    }
    return $this->jsonResponse('Can you see this message?', 'error');
  }

  public function jsonResponse($message, $type) {
    return new JsonResponse(
      [
        'data' => [
          'message' => $message,
          'type' => $type
        ]
      ]
    );
  }
}