<?php
/**
 * @file
 * Recording classes for Zoom API.
 *
 * @see https://zoom.github.io/api/#cloud-recording
 */
namespace Drupal\zoom_conference\Api;

/**
 * Zoom API Recording Class.
 */
class ZoomAPIRecording extends ZoomAPI {

  /**
   * List Recordings.
   *
   * @param string $host_zoom_user_id
   *   The meeting host zoom user ID.
   * @param string $meeting_id
   *   The (optional) meeting number.
   * @param string $from
   *   The meeting start time after this date, MM/dd/yyyy hh:mm a. For example:
   *   11/05/2014 09:05 pm. Use the host’s time zone, if host has not set time
   *   zone, will use GMT.
   * @param string $to
   *   The meeting start time before this date, MM/dd/yyyy hh:mm a.
   * @param int $page_size
   *   The amount of records returns within a single API call. Defaults to 30.
   *   Max of 300 meetings.
   * @param int $page_number
   *   Current page number of returned records. Default to 1.
   *
   * @return array
   *   An array of cloud recording meetings.
   */
  public function list($host_zoom_user_id, $from, $to, $options) {
    $data = [
      'from' => $from, // Start date
      'to' => $to, // End date
    ];

    /*
     * The amount of records returns within a single API call.
     * Default: 30. Max: 300.
     */
    if (isset($options['page_size'])) {
      $data['page_size'] = $options['page_size'];
    }

    /*
     * Next page token, used to paginate through large result sets. A next page
     * token will be returned whenever the set of available result list exceeds
     * page size. The expiration period is 15 minutes.
     */
    if (isset($options['next_page_token'])) {
      $data['next_page_token'] = $options['next_page_token'];
    }

    /*
     * Query mc. Default: false.
     */
    if (isset($options['mc'])) {
      $data['mc'] = $options['mc'];
    }

    /*
     * Query trash. Default: false.
     */
    if (isset($options['trash'])) {
      $data['trash'] = $options['trash'];
    }

    return $this->sendRequest('users/' . $host_zoom_user_id . '/recordings', 'GET' , $data);
  }

  /**
   * Retrieve a meeting's recordings.
   *
   * @param string $meeting_id
   *   The zoom meeting ID.
   *
   * @return array
   *   The meeting recording information.
   */
  public function get($meeting_id) {
    return $this->sendRequest('meetings/' . $meeting_id . '/recordings', 'GET', []);
  }

}
