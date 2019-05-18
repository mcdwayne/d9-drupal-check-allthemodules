<?php
/**
 * @file
 * Contains \Drupal\droogle_hangout\DroogleHangoutController.
 */

namespace Drupal\droogle_hangout\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for the Hangout creation ajax call.
 */
class DroogleHangoutController {
  /**
   * Ajax call to create Hangout when Start Hangout is clicked.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function ajaxCreateHangout(Request $request) {
    global $base_url;
    $user = \Drupal::currentUser();
    $ajax_email = $request->request->get('ajaxemail');
    $email_array = explode(",", $ajax_email);
    $caldate = null !== $request->request->get('caldate') ? $request->request->get('caldate') : '';

    foreach ($email_array as $email) {
      $json_attendees[] = ', { "email": "' . $email . '" }';
    }

    $json_attendee = implode(",", $json_attendees);
    $json_attendee = str_replace(",,", ",", $json_attendee);

    $access_token = NULL;
    if (empty($access_token)) {
      if ($path = libraries_get_path('google-api-php-client')) {
        require_once $path . '/src/Google_Client.php';
        require_once $path . '/src/contrib/Google_CalendarService.php';
      }

      //\Drupal::logger('google')->notice($path);
      // Console account email, client id, client secret
      // and refresh token entered on admin screen.
      $config = \Drupal::config('droogle_hangout.droogle');
      $droogle_hangout_master_email = Html::escape($config->get('email'));
      $droogle_hangout_clientid = Html::escape($config->get('clientid'));
      $droogle_hangout_client_secret = Html::escape($config->get('secret'));
      $droogle_hangout_refresh_token = Html::escape($config->get('token'));

      // Initialize access to Google.
      $client = new \Google_Client();
      $client->setClientId($droogle_hangout_clientid);
      $client->setClientSecret($droogle_hangout_client_secret);
      $client->setRedirectUri("$base_url/droogle_hangout_get_token");
      $client->setAccessType('offline');

      // Initialize access to Calendar as service.
      $service = new \Google_CalendarService($client);
      // If Access Token Expired (uses Google_OAuth2 class),
      // refresh access token by refresh token.
      if ($client->isAccessTokenExpired()) {
        $client->refreshToken($droogle_hangout_refresh_token);
      }
      // If client got access token successfuly - perform operations.
      $access_tokens = json_decode($client->getAccessToken());
      $new_access_token = $access_tokens->access_token;
    }
    $access_token = $new_access_token;
    if ($caldate == '') {
      $start = date3339(time());
      $end = date3339(strtotime('+1 hour', time()));
    }
    else {
      $start_timestamp = strtotime($caldate . ' ' . $user->getTimeZone());
      $start = date3339($start_timestamp);
      $end = date3339(strtotime('+1 hour', $start_timestamp));
    }
    $calendar_output = shell_exec('curl "https://www.googleapis.com/calendar/v3/calendars/' . $droogle_hangout_master_email . '/events?access_token=' . $access_token . '" -H "Content-Type: application/json" -d \' { "summary": "Google Hangout", "start": { "dateTime": "' . $start . '" }, "end": { "dateTime": "' . $end . '" }, "attendees": [ { "email": "{' . $user->getEmail() . '}" }' . $json_attendee . ' ], "reminders": { "overrides":[ ] } }\' -v');
    $calendar_json = Json::decode($calendar_output);
    $hangout_link = $calendar_json['hangoutLink'];

    // Send email out.
    $to = $user->getEmail();
    $cc = $_POST['ajaxemail'];
    $subj = 'You are invited to a Google Hangout';
    $scheduled_time = $caldate != '' ? 'The hangout is scheduled for ' . $caldate . '.  ' : '';
    $body = $scheduled_time . 'Please visit ' . $hangout_link . ' to join the hangout.';
    $from = $to;

    $params = array(
      'body' => array($body),
      'subject' => $subj,
      'headers' => array(
        'Cc' => $cc,
        'Bcc' => ''
      ),
    );

    \Drupal::service('plugin.manager.mail')->mail('droogle_hangout', 'droogle_hangout_alert', $to, $user->getPreferredLangcode(), $params, $from);

    return new JsonResponse(array(
      'successful' => 'Hangout created',
      'hangoutlink' => $hangout_link,
      'invitee_email' => $ajax_email,
    ));
  }
}
