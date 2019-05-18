<?php

namespace Drupal\discourse_sso;

use Drupal\discourse_sso\SingleSignOnBase;

/**
 * Propagate a user logout in Drupal to the associated Discourse forum.
 *
 * cf. https://meta.discourse.org/t/discourse-sso-logout/28509/21
 */
class Logout extends SingleSignOnBase {

  public function logout($uid) {
    try {
      $discourse_user = $this->getDiscourseUserByExternalId($uid);
      $discourse_id = $discourse_user->user->id;

      $url = $this->url . '/admin/users/' . $discourse_id . '/log_out';
      $request = $this->client->request('POST', $url, $this->getDefaultParameter());
    }
    catch (Exception $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }
  }

  protected function getDiscourseUserByExternalId($uid, $method = 'GET') {
    $url = $this->url . '/users/by-external/' . $uid . '.json';

    try {
      $request = $this->client->request($method, $url, $this->getDefaultParameter());
      if ($request->getStatusCode() === 200) {
        $response = json_decode($request->getBody());
        if ($response) {
          return $response;
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }

    return FALSE;
  }
}
