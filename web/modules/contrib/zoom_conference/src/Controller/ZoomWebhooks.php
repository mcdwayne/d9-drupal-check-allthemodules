<?php

namespace Drupal\zoom_conference\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

module_load_include('php', 'zoom_conference', 'src/ZoomApi');
module_load_include('php', 'zoom_conference', 'src/ZoomMeetingApi');
module_load_include('php', 'zoom_conference', 'src/ZoomRecordingApi');

use Drupal\zoom_conference\Api\ZoomAPI;
use Drupal\zoom_conference\Api\ZoomAPIMeeting;
use Drupal\zoom_conference\Api\ZoomAPIRecording;

/**
 * Web Hooks Controller Class.
 *
 * @see: https://developer.zoom.us/docs/webhooks/
 *
 * To Enable Push Notifications:
 * Go to Credential page (https://developer.zoom.us/me) and click Enable Push Notifications.
 *
 * There is an additional setting in https://zoom.us/account/setting?tab=recording 
 * to receive RECORDING_MEETING_COMPLETED notification
 */
class ZoomWebhooks extends ControllerBase {

  /**
   * Endpoint for the web hooks callback.
   */
  public function callback(Request $request) {
    // Config object.
    $config = \Drupal::config('zoom_conference.settings');

    // Webhooks not enabled.
    if (!$config->get('zoom_conference_webhooks_enabled')) {
      throw new AccessDeniedHttpException();
    }

    // Validate basic auth.
    if (empty($_SERVER['PHP_AUTH_USER']) ||
      empty($_SERVER['PHP_AUTH_PW']) ||
      ($_SERVER['PHP_AUTH_USER'] != $config->get('zoom_conference_webhooks_username')) ||
      ($_SERVER['PHP_AUTH_PW'] == $config->get('zoom_conference_webhooks_password'))) {
      throw new AccessDeniedHttpException();
    }

    #TODO: we should check the params, and throw a bad request if not set.

    // Get meeting id.
    $meeting_id = $request->request->get('id');

    // Get meeting uuid.
    $meeting_uuid = $request->request->get('uuid');
    
    // Get host id.
    $host_id = $request->request->get('host_id');

    // Get status.
    $status = $request->request->get('status');

    // Load the meeting node via meeting id, and ensure it is ok.
    $query = \Drupal::entityQuery('node');
    $query
      ->condition('status', 1)
      ->condition('type', 'zoom_meeting')
      ->condition('field_meeting_id', $meeting_id);
    $entity_ids = $query->execute();

    // Meeting not found.
    if (empty($entity_ids)) {
      throw new NotFoundHttpException();
    }

    // Load the meeting.
    $nid = array_pop($entity_ids);
    $meeting = node_load($nid);

    #TODO: If we wanted to do additional validation, we could cross-reference the uuid here.

    // Perform status actions.
    switch ($status) {
      case "STARTED":
        #TODO: set started flag on meeting.
        break;

      case "ENDED":
        #TODO: set ended flag on meeting.
        break;

      // Attendee has Joined Before Host.
      case "JBH":
        #TODO: alert the host?
        break;

      // Meeting hasn't started, but attendee(s) are waiting.
      case "JOIN":
        #TODO: alert the host.
        break;

      case "RECORDING_MEETING_COMPLETED":
        // Use API to retrieve the recordings and update the meeting with them.
        $recordingApi = new ZoomAPIRecording;
        $meeting_info = $recordingApi->get($meeting_id);
        if ($meeting_info !== FALSE) {
          try {
            $meeting->set('field_zoom_meeting_cloud_data', json_encode($meeting_info));
            $meeting->save();
          }
          catch (\Exception $e) {
            watchdog('zoom_conference',
              'Cannot update meeting during Webhook => !data',
              [
                '!data' => '<pre>' . print_r($meeting_info, TRUE) . '</pre>',
              ],
              WATCHDOG_DEBUG);
          }
        }
        break;
    }

    // Debug.
    if ($config->get('zoom_conference_debug')) {
      $params = $request->query->all();

      watchdog('zoom_conference',
        'Webhook => !data',
        [
          '!data' => '<pre>' . print_r($params, TRUE) . '</pre>',
        ],
        WATCHDOG_DEBUG);
    }

    // A-ok!
    return new JsonResponse(['status' => 'ok']);
  }
}
