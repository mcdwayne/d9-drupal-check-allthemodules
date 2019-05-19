<?php
/**
 * @file
 * Meeting classes for Zoom API.
 */
namespace Drupal\zoom_conference\Api;

/**
 * Zoom API Meeting Class.
 */
class ZoomAPIMeeting extends ZoomAPI {

  /**
   * Create Meeting.
   *
   * Note: See the api documentation regarding the 'status' variable values in
   * the response. Also notes how to pass the user name in the join URL to
   * bypass the username dialog.
   *
   * @param string $host_zoom_user_id
   *   The zoom user ID of the meeting host.
   * @param string $topic
   *   The meeting topic. Max 300 chars.
   * @param int $type
   *   The type of meeting:
   *   - 1: Instant meeting.
   *   - 2: Normal scheduled meeting (default).
   *   - 3: Recurring meeting with no fixed time.
   *   - 8: Recurring meeting with fixed time.
   * @param array $options
   *   An array of optional meeting options.
   *
   * @return array
   *   An array of the meeting.
   */
  public function create($host_zoom_user_id, $topic, $type = 2, $options = array()) {
    $data = [
      'topic' => substr($topic, 0, 300),
      'type' => $type,
    ];

    /*
     * Meeting start time, in the format “yyyy-MM-dd’T'HH:mm:ss'Z’”, should be GMT time.
     * In the format “yyyy-MM-dd’T'HH:mm:ss”, should be local time, need to specify the
     * time zone. Only used for scheduled meeting and recurring meeting with fixed time.
     */
    if (isset($options['start_time'])) {
      $data['start_time'] = $options['start_time'];
    }

    /*
     * Meeting duration (minutes). Used for scheduled meeting only.
     */
    if (isset($options['duration'])) {
      $data['duration'] = $options['duration'];
    }

    /*
     * Timezone to format start_time, like “America/Los_Angeles”. For scheduled meeting
     * only. For this parameter value please refer to the id value in
     * https://zoom.github.io/api/#timezones list.
     */
    if (isset($options['timezone'])) {
      $data['timezone'] = $options['timezone'];
    }

    /*
     * Password to join the meeting. Password may only contain the following
     * characters: [a-z A-Z 0-9 @ - _ *]. Max of 10 characters.
     */
    if (isset($options['password'])) {
      $data['password'] = $options['password'];
    }

    /*
     * Meeting description.
     */
    if (isset($options['agenda'])) {
      $data['agenda'] = $options['agenda'];
    }

    /*
     * Recurrence meeting type
     * 1 Daily
     * 2 Weekly
     * 3 Monthly
     */
    if (isset($options['recurrence.type'])) {
      $data['recurrence']['type'] = $options['recurrence.type'];
    }

    /*
     * Recurrence meeting repeat interval. For a Daily Meeting, max of 90.
     * For a Weekly Meeting, max of 12. For a Monthly Meeting, max of 3.
     */
    if (isset($options['recurrence.repeat_interval'])) {
      $data['recurrence']['repeat_interval'] = $options['recurrence.repeat_interval'];
    }

    /*
     * Recurrence Meeting Occurs on week days, multiple value separated by comma
     * 1 Sunday
     * 2 Monday
     * 3 Tuesday
     * 4 Wednesday
     * 5 Thursday
     * 6 Friday
     * 7 Saturday
     */
    if (isset($options['recurrence.weekly_days'])) {
      $data['recurrence']['weekly_days'] = $options['recurrence.weekly_days'];
    }

    /*
     * Recurrence Meeting Occurs on a month day. The value range is from 1 to 31.
     */
    if (isset($options['recurrence.monthly_day'])) {
      $data['recurrence']['monthly_day'] = $options['recurrence.monthly_day'];
    }

    /*
     * Recurrence Meeting Occurs on the week of a month.
     * -1 Last week
     * 1 First week
     * 2 Second week
     * 3 Third week
     * 4 Fourth week
     */
    if (isset($options['recurrence.monthly_week'])) {
      $data['recurrence']['monthly_week'] = $options['recurrence.monthly_week'];
    }

    /*
     * Recurrence Meeting Occurs on the week day of a month
     * 1 Sunday
     * 2 Monday
     * 3 Tuesday
     * 4 Wednesday
     * 5 Thursday
     * 6 Friday
     * 7 Saturday
     */
    if (isset($options['recurrence.monthly_week_day'])) {
      $data['recurrence']['monthly_week_day'] = $options['recurrence.monthly_week_day'];
    }

    /*
     * Recurrence Meeting End occurrences times.
     * Default: 1; Max: 50.
     */
    if (isset($options['recurrence.end_times'])) {
      $data['recurrence']['end_times'] = $options['recurrence.end_times'];
    }

    /*
     * Recurrence Meeting End Date. Should be UTC time, such as 2017-11-25T12:00:00Z.
     */
    if (isset($options['recurrence.end_date_time'])) {
      $data['recurrence']['end_date_time'] = $options['recurrence.end_date_time'];
    }

    /*
     * Start video when host join meeting. Boolean.
     */
    if (isset($options['settings.host_video'])) {
      $data['settings']['host_video'] = $options['settings.host_video'];
    }

    /*
     * Start video when participants join meeting. Boolean.
     */
    if (isset($options['settings.participant_video'])) {
      $data['settings']['participant_video'] = $options['settings.participant_video'];
    }

    /*
     * Host meeting in China. Boolean.
     */
    if (isset($options['settings.cn_meeting'])) {
      $data['settings']['cn_meeting'] = $options['settings.cn_meeting'];
    }

    /*
     * Host meeting in India. Boolean.
     */
    if (isset($options['settings.in_meeting'])) {
      $data['settings']['in_meeting'] = $options['settings.in_meeting'];
    }

    /*
     * Join meeting before host start the meeting. Only used for scheduled or
     * recurring meetings. Boolean. Default: false.
     */
    if (isset($options['settings.join_before_host'])) {
      $data['settings']['join_before_host'] = $options['settings.join_before_host'];
    }

    /*
     * Mute participants upon entry. Boolean. Default: false.
     */
    if (isset($options['settings.mute_upon_entry'])) {
      $data['settings']['mute_upon_entry'] = $options['settings.mute_upon_entry'];
    }

    /*
     * Enable watermark when viewing the shared screen. Boolean. Default: false.
     */
    if (isset($options['settings.watermark'])) {
      $data['settings']['watermark'] = $options['settings.watermark'];
    }

    /*
     * Use Personal Meeting ID. Only used for scheduled meetings and recurring
     * meetings with no fixed time. Boolean. Default: false.
     */
    if (isset($options['settings.use_pmi'])) {
      $data['settings']['use_pmi'] = $options['settings.use_pmi'];
    }

    /*
     * 0 Automatically Approve
     * 1 Manually Approve
     * 2 No Registration Required (default)
     */
    if (isset($options['settings.approval_type'])) {
      $data['settings']['approval_type'] = $options['settings.approval_type'];
    }

    /*
     * Registration type. Used for recurring meeting with fixed time only.
     * 1 Attendees register once and can attend any of the occurrences (default)
     * 2 Attendees need to register for each occurrence to attend
     * 3 Attendees register once and can choose one or more occurrences to attend
     */
    if (isset($options['settings.registration_type'])) {
      $data['settings']['registration_type'] = $options['settings.registration_type'];
    }

    /*
     * Meeting audio options
     * both Both Telephony and VoIP (default)
     * telephony Telephony only
     * voip VoIP only
     */
    if (isset($options['settings.audio'])) {
      $data['settings']['audio'] = $options['settings.audio'];
    }

    /*
     * local Record to local device
     * cloud Record to cloud
     * none No Recording (default)
     */
    if (isset($options['settings.auto_recording'])) {
      $data['settings']['auto_recording'] = $options['settings.auto_recording'];
    }

    /*
     * Only signed-in users can join this meeting.
     */
    if (isset($options['settings.enforce_login'])) {
      $data['settings']['enforce_login'] = $options['settings.enforce_login'];
    }

    /*
     * Only signed-in users with specified domains can join meetings.
     */
    if (isset($options['settings.enforce_login_domains'])) {
      $data['settings']['enforce_login_domains'] = $options['settings.enforce_login_domains'];
    }

    /*
     * Alternative hosts emails or IDs. Multiple value separated by comma.
     */
    if (isset($options['settings.alternative_hosts'])) {
      $data['settings']['alternative_hosts'] = $options['settings.alternative_hosts'];
    }

    return $this->sendRequest('users/' . $host_zoom_user_id . '/meetings', 'POST', $data);
  }

  /**
   * Delete Meeting.
   *
   * @param string $meeting_id
   *   The zoom provided meeting ID.
   * @param string $occurrence_id
   *   Optional meeting occurrence ID for use with recurring meetings.
   *
   * @return array
   *   An array of the transaction metadata.
   */
  public function delete($meeting_id, $occurrence_id = NULL) {
    $data = [];

    if ($occurrence_id) {
      $data['occurrence'] = $occurrence_id;
    }

    return $this->sendRequest('meetings/' . $meeting_id, 'DELETE', $data);
  }

  /**
   * List Meetings.
   *
   * @param string $host_zoom_user_id
   *   The host user ID.
   * @param string @type
   *   The meeting type { scheduled, live }
   * @param int $page_size
   *   Number of results per page.
   * @param int $page_number
   *   The page number to return.
   *
   * @return array
   *   An array of meetings.
   *
   * @todo the use of the host ID makes this seem like a user specific list.
   * Need to check how to get a list of all meetings. API docs says this does
   * not include instant meetings either.
   */
  public function list($host_zoom_user_id, $type = 'live', $page_size = 30, $page_number = 1) {
    return $this->sendRequest('users/' . $host_zoom_user_id . '/meetings', 'GET', [
      'type' => $type,
      'page_size' => $page_size,
      'page_number' => $page_number,
    ]);
  }

  /**
   * Get Meeting Info.
   *
   * @param string $meeting_id
   *   The zoom generated meeting ID.
   *
   * @return array
   *   An array containing the meeting details.
   */
  public function get($meeting_id) {
    return $this->sendRequest('meetings/' . $meeting_id, 'GET', []);
  }

  /**
   * Update Meeting.
   *
   * @param string $meeting_id
   *   The zoom generated meeting ID.
   * @param string $host_zoom_user_id
   *   The host user ID.
   * @param array $options
   *   Optional meeting configuration.
   *
   * @return array
   *   An array containing the transaction details.
   */
  public function update($meeting_id, $options = array()) {
    /*
     * Meeting topic.
     */
    if (isset($options['topic'])) {
      $data['topic'] = substr($options['topic'], 0, 300);
    }

    /*
     * Meeting type.
     * 1 Instant Meeting
     * 2 Scheduled Meeting (default)
     * 3 Recurring Meeting with no fixed time
     * 8 Recurring Meeting with fixed time
     */
    if (isset($options['type'])) {
      $data['type'] = $options['type'];
    }

    /*
     * Meeting start time, in the format “yyyy-MM-dd’T'HH:mm:ss'Z’”, should be GMT time.
     * In the format “yyyy-MM-dd’T'HH:mm:ss”, should be local time, need to specify the
     * time zone. Only used for scheduled meeting and recurring meeting with fixed time.
     */
    if (isset($options['start_time'])) {
      $data['start_time'] = $options['start_time'];
    }

    /*
     * Meeting duration (minutes). Used for scheduled meeting only.
     */
    if (isset($options['duration'])) {
      $data['duration'] = $options['duration'];
    }

    /*
     * Timezone to format start_time, like “America/Los_Angeles”. For scheduled meeting
     * only. For this parameter value please refer to the id value in
     * https://zoom.github.io/api/#timezones list.
     */
    if (isset($options['timezone'])) {
      $data['timezone'] = $options['timezone'];
    }

    /*
     * Password to join the meeting. Password may only contain the following
     * characters: [a-z A-Z 0-9 @ - _ *]. Max of 10 characters.
     */
    if (isset($options['password'])) {
      $data['password'] = $options['password'];
    }

    /*
     * Meeting description.
     */
    if (isset($options['agenda'])) {
      $data['agenda'] = $options['agenda'];
    }

    /*
     * Recurrence meeting type
     * 1 Daily
     * 2 Weekly
     * 3 Monthly
     */
    if (isset($options['recurrence.type'])) {
      $data['recurrence']['type'] = $options['recurrence.type'];
    }

    /*
     * Recurrence meeting repeat interval. For a Daily Meeting, max of 90.
     * For a Weekly Meeting, max of 12. For a Monthly Meeting, max of 3.
     */
    if (isset($options['recurrence.repeat_interval'])) {
      $data['recurrence']['repeat_interval'] = $options['recurrence.repeat_interval'];
    }

    /*
     * Recurrence Meeting Occurs on week days, multiple value separated by comma
     * 1 Sunday
     * 2 Monday
     * 3 Tuesday
     * 4 Wednesday
     * 5 Thursday
     * 6 Friday
     * 7 Saturday
     */
    if (isset($options['recurrence.weekly_days'])) {
      $data['recurrence']['weekly_days'] = $options['recurrence.weekly_days'];
    }

    /*
     * Recurrence Meeting Occurs on a month day. The value range is from 1 to 31.
     */
    if (isset($options['recurrence.monthly_day'])) {
      $data['recurrence']['monthly_day'] = $options['recurrence.monthly_day'];
    }

    /*
     * Recurrence Meeting Occurs on the week of a month.
     * -1 Last week
     * 1 First week
     * 2 Second week
     * 3 Third week
     * 4 Fourth week
     */
    if (isset($options['recurrence.monthly_week'])) {
      $data['recurrence']['monthly_week'] = $options['recurrence.monthly_week'];
    }

    /*
     * Recurrence Meeting Occurs on the week day of a month
     * 1 Sunday
     * 2 Monday
     * 3 Tuesday
     * 4 Wednesday
     * 5 Thursday
     * 6 Friday
     * 7 Saturday
     */
    if (isset($options['recurrence.monthly_week_day'])) {
      $data['recurrence']['monthly_week_day'] = $options['recurrence.monthly_week_day'];
    }

    /*
     * Recurrence Meeting End occurrences times.
     * Default: 1; Max: 50.
     */
    if (isset($options['recurrence.end_times'])) {
      $data['recurrence']['end_times'] = $options['recurrence.end_times'];
    }

    /*
     * Recurrence Meeting End Date. Should be UTC time, such as 2017-11-25T12:00:00Z.
     */
    if (isset($options['recurrence.end_date_time'])) {
      $data['recurrence']['end_date_time'] = $options['recurrence.end_date_time'];
    }

    /*
     * Start video when host join meeting. Boolean.
     */
    if (isset($options['settings.host_video'])) {
      $data['settings']['host_video'] = $options['settings.host_video'];
    }

    /*
     * Start video when participants join meeting. Boolean.
     */
    if (isset($options['settings.participant_video'])) {
      $data['settings']['participant_video'] = $options['settings.participant_video'];
    }

    /*
     * Host meeting in China. Boolean.
     */
    if (isset($options['settings.cn_meeting'])) {
      $data['settings']['cn_meeting'] = $options['settings.cn_meeting'];
    }

    /*
     * Host meeting in India. Boolean.
     */
    if (isset($options['settings.in_meeting'])) {
      $data['settings']['in_meeting'] = $options['settings.in_meeting'];
    }

    /*
     * Join meeting before host start the meeting. Only used for scheduled or
     * recurring meetings. Boolean. Default: false.
     */
    if (isset($options['settings.join_before_host'])) {
      $data['settings']['join_before_host'] = $options['settings.join_before_host'];
    }

    /*
     * Mute participants upon entry. Boolean. Default: false.
     */
    if (isset($options['settings.mute_upon_entry'])) {
      $data['settings']['mute_upon_entry'] = $options['settings.mute_upon_entry'];
    }

    /*
     * Enable watermark when viewing the shared screen. Boolean. Default: false.
     */
    if (isset($options['settings.watermark'])) {
      $data['settings']['watermark'] = $options['settings.watermark'];
    }

    /*
     * Use Personal Meeting ID. Only used for scheduled meetings and recurring
     * meetings with no fixed time. Boolean. Default: false.
     */
    if (isset($options['settings.use_pmi'])) {
      $data['settings']['use_pmi'] = $options['settings.use_pmi'];
    }

    /*
     * 0 Automatically Approve
     * 1 Manually Approve
     * 2 No Registration Required (default)
     */
    if (isset($options['settings.approval_type'])) {
      $data['settings']['approval_type'] = $options['settings.approval_type'];
    }

    /*
     * Registration type. Used for recurring meeting with fixed time only.
     * 1 Attendees register once and can attend any of the occurrences (default)
     * 2 Attendees need to register for each occurrence to attend
     * 3 Attendees register once and can choose one or more occurrences to attend
     */
    if (isset($options['settings.registration_type'])) {
      $data['settings']['registration_type'] = $options['settings.registration_type'];
    }

    /*
     * Meeting audio options
     * both Both Telephony and VoIP (default)
     * telephony Telephony only
     * voip VoIP only
     */
    if (isset($options['settings.audio'])) {
      $data['settings']['audio'] = $options['settings.audio'];
    }

    /*
     * local Record to local device
     * cloud Record to cloud
     * none No Recording (default)
     */
    if (isset($options['settings.auto_recording'])) {
      $data['settings']['auto_recording'] = $options['settings.auto_recording'];
    }

    /*
     * Only signed-in users can join this meeting.
     */
    if (isset($options['settings.enforce_login'])) {
      $data['settings']['enforce_login'] = $options['settings.enforce_login'];
    }

    /*
     * Only signed-in users with specified domains can join meetings.
     */
    if (isset($options['settings.enforce_login_domains'])) {
      $data['settings']['enforce_login_domains'] = $options['settings.enforce_login_domains'];
    }

    /*
     * Alternative hosts emails or IDs. Multiple value separated by comma.
     */
    if (isset($options['settings.alternative_hosts'])) {
      $data['settings']['alternative_hosts'] = $options['settings.alternative_hosts'];
    }

    return $this->sendRequest('meetings/' . $meeting_id, 'PATCH', $data);
  }

  /**
   * End meeting.
   *
   * @param string $meeting_id
   *   The zoom generated meeting ID.
   */
  public function end_meeting($meeting_id) {
    return $this->sendRequest('meetings/' . $meeting_id, 'PUT', [
      'action' => 'end',
    ]);
  }

  /**
   * List registrants of a meeting.
   *
   * @param string $meeting_id
   *   The zoom generated meeting ID.
   */
  public function list_registrants($meeting_id, $options = []) {
    $data = [
      'meetingId' => $meeting_id,
    ];

    /*
     * The meeting occurrence ID.
     */
    if (isset($options['occurrence_id'])) {
      $data['occurrence_id'] = $options['occurrence_id'];
    }

    /*
     * The meeting occurrence IDThe registrant status
     * pending registrants status is pending
     * approved registrants status is approved (default)
     * denied registrants status is denied
     */
    if (isset($options['status'])) {
      $data['status'] = $options['status'];
    }

    /*
     * The amount of records returns within a single API call.
     * Default: 30; Max: 300.
     */
    if (isset($options['page_size'])) {
      $data['page_size'] = $options['page_size'];
    }

    /*
     * Current page number of returned records. Default: 1.
     */
    if (isset($options['page_number'])) {
      $data['page_number'] = $options['page_number'];
    }

    return $this->sendRequest('meetings/' . $meeting_id. '/registrants', 'GET', [
      'action' => 'end',
    ]);
  }

  /**
   * Add a meeting registrant.
   *
   * @param string $meeting_id
   *   The zoom generated meeting ID.
   */
  public function add_registrant($meeting_id, $email, $first_name, $last_name, $options) {
    $data = [
      'email' => $email,
      'first_name' => $first_name,
      'last_name' => $last_name,
    ];

    /*
     * Address.
     */
    if (isset($options['address'])) {
      $data['address'] = $options['address'];
    }

    /*
     * City.
     */
    if (isset($options['city'])) {
      $data['city'] = $options['city'];
    }

    /*
     * Country.
     */
    if (isset($options['country'])) {
      $data['country'] = $options['country'];
    }

    /*
     * Zip/Postal Code.
     */
    if (isset($options['zip'])) {
      $data['zip'] = $options['zip'];
    }

    /*
     * State/Province.
     */
    if (isset($options['state'])) {
      $data['state'] = $options['state'];
    }

    /*
     * Phone.
     */
    if (isset($options['phone'])) {
      $data['phone'] = $options['phone'];
    }

    /*
     * Industry.
     */
    if (isset($options['industry'])) {
      $data['industry'] = $options['industry'];
    }

    /*
     * Organization.
     */
    if (isset($options['org'])) {
      $data['org'] = $options['org'];
    }

    /*
     * Job Title.
     */
    if (isset($options['job_title'])) {
      $data['job_title'] = $options['job_title'];
    }

    /*
     * Purchasing Time Frame.
     * Within a month
     * 1-3 months
     * 4-6 months
     * More than 6 months
     * No timeframe
     */
    if (isset($options['purchasing_time_frame'])) {
      $data['purchasing_time_frame'] = $options['purchasing_time_frame'];
    }

    /*
     * Role in Purchase Process.
     * Decision Maker
     * Evaluator/Recommender
     * Influencer
     * Not involved
     */
    if (isset($options['role_in_purchase_process'])) {
      $data['role_in_purchase_process'] = $options['role_in_purchase_process'];
    }

    /*
     * Number of Employees
     * 1-20
     * 21-50
     * 51-100
     * 101-500
     * 500-1,000
     * 1,001-5,000
     * 5,001-10,000
     * More than 10,000
     */
    if (isset($options['no_of_employees'])) {
      $data['no_of_employees'] = $options['no_of_employees'];
    }

    /*
     * Questions & Comments
     */
    if (isset($options['comments'])) {
      $data['comments'] = $options['comments'];
    }

    /*
     * Custom Comments
     */
    if (isset($options['custom_questions'])) {
      $data['custom_questions'] = $options['custom_questions'];
    }

    return $this->sendRequest('meetings/' . $meeting_id . '/registrants', 'POST', $data);
  }

  /**
   * Update a meeting registrant's status.
   *
   * @param string $meeting_id
   *   The zoom generated meeting ID.
   */
  public function update_registrant_status($meeting_id, $action, $registrants, $options) {
    $data = [
      'action' => $action, // { approve, cancel, deny }
    ];

    /*
     * The meeting occurrence ID
     */
    if (isset($options['occurrence_id'])) {
      $data['occurrence_id'] = $options['occurrence_id'];
    }

    if (!empty($registrants)) {
      $data['registrants'] = [];

      foreach ($registrants as $registrant) {
        $data['registrants'][] = [
          'id' => $registrant['id'],
          'email' => $registrant['email'],
        ];
      }
    }

    return $this->sendRequest('meetings/' . $meeting_id . '/registrants/status', 'PUT', $data);
  }

  /**
   * Get a list of users on the current account.
   *
   * TODO: move to users class.
   */
  public function get_users() {
    return $this->sendRequest('users/', 'GET', []);
  }

}
