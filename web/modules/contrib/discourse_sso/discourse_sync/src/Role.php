<?php

namespace Drupal\discourse_sync;

use \Drupal\discourse_sso\SingleSignOnBase;

/**
 * Synchronize Drupal roles to discourse using its json API.
 *
 * Discourse API doesn't get auth info from JSON data:
 * https://meta.discourse.org/t/json-vs-url-encoded-api-calls-json-returns-bad-csrf/22406
 * Hence add api_key and api_username to URL.
 */
class Role extends SingleSignOnBase {

  public function createRole($name, $label = '', $method = 'POST') {
    $url = $this->url . '/admin/groups';
    $parameters = [
      'json' => [
        'group' => [
          'name' => $name,
          'full_name' => $label
        ]
      ]
    ] + $this->getDefaultParameter();
    try {
      $request = $this->client->request($method, $url, $parameters);
      $response = json_decode($request->getBody());

      if (!empty($response)) {
        $id = $response->basic_group->id;
        $message = $this->t('Discourse group #@id: @name created.', ['@id' => $id, '@name' => $name]);
        $this->notify($message);
      }

      return $response;
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }
  }

  public function updateRole($name, $label = '', $method = 'PUT') {
    $id = $this->getGroupIdByName($name);
    if (!$id) {
      return $this->createRole($name, $label);
    }

    $url = $this->url . '/groups/' . $id . '.json';
    $parameters = [
      'json' => [
        'group' => [
          'full_name' => $label
        ]
      ]
    ] + $this->getDefaultParameter();
    try {
      $request = $this->client->request($method, $url, $parameters);
      $response = json_decode($request->getBody());

      if (!empty($response)
          && isset($response->success)) {
        $message = $this->t('Discourse group: @name updated.', ['@name' => $name]);
        $this->notify($message);
      }

      return $response;
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }
  }

  public function deleteRole($name, $method = 'DELETE') {
    $id = $this->getGroupIdByName($name);
    if (!$id) {
      $message = $this->t('Discourse not synchronized.');
      $message .= ' ' . $this->t('Deleted group @name does not exist.', ['@name' => $name]);
      $this->notify($message);
      return;
    }

    $url = $this->url . '/admin/groups/' . $id . '.json';
    try {
      $request = $this->client->request($method, $url, $this->getDefaultParameter());
      if ($request->getStatusCode() === 200) {
        $response = json_decode($request->getBody());
        $message = $this->t('Discourse group #@id: @name deleted.', ['@id' => $id, '@name' => $name]);
        $this->notify($message);

        return $response;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }
  }

  public function syncUserRoles($username, $roles) {
    $user = $this->getUserByUsername($username);
    if (!$user) {
      $message = $this->t('Discourse not synchronized.');
      $message .= ' ' . $this->t('User @name does not exist.', ['@name' => $username]);
      $this->notify($message);
      return;
    }

    $usergroups = $this->getCustomUserGroups($user);

    // New roles
    $new = array_diff($roles, $usergroups);
    foreach ($new as $role) {
      $this->assignRole($username, $role);
    }

    // Removed roles
    $obsolete = array_diff($usergroups, $roles);
    foreach ($obsolete as $role) {
      $this->divestRole($username, $role);
    }

    return;
  }

  public function assignRole($username, $role, $method = 'PUT') {
    $gid = $this->getGroupIdByName($role);
    if (!$gid) {
      $message = $this->t('Discourse not synchronized.');
      $message .= ' ' . $this->t('Group @role does not exist.', ['@role' => $role]);
      $this->notify($message);
      return;
    }

    $url = $this->url . '/groups/' . $gid . '/members.json';
    $parameters = [
      'json' => [
        'usernames' => $username
      ]
    ] + $this->getDefaultParameter();
    try {
      $request = $this->client->request($method, $url, $parameters);
      $response = json_decode($request->getBody());

      if ($response && key($response) === 'success') {
        $message = $this->t('Discourse user @username: assigned to @role.', [
          '@username' => $username,
          '@role' => $role
        ]);
        $this->notify($message);
      }

      return $response;
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }
  }

  public function divestRole($username, $role, $method = 'DELETE') {
    $gid = $this->getGroupIdByName($role);
    if (!$gid) {
      $message = $this->t('Discourse not synchronized.');
      $message .= ' ' . $this->t('Group @role does not exist.', ['@role' => $role]);
      $this->notify($message);
      return;
    }

    $user = $this->getUserByUsername($username);
    if (!$user) {
      $message = $this->t('Discourse not synchronized.');
      $message .= ' ' . $this->t('User @user does not exist.', ['@user' => $username]);
      $this->notify($message);
      return;
    }

    $url = $this->url . '/groups/' . $gid . '/members.json';
    $parameter = [
      'json' => [
        'user_id' => $user->user->id
      ]
    ] + $this->getDefaultParameter();
    try {
      $request = $this->client->request($method, $url, $parameter);
      if ($request->getStatusCode() === 200) {
        $response = json_decode($request->getBody());
        $message = $this->t('Discourse user @username: @role divested.', [
          '@username' => $username,
          '@role' => $role
        ]);
        $this->notify($message);

        return $response;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }
  }

  protected function getGroupIdByName($name, $method = 'GET') {
    $url = $this->url . '/groups/search.json';

    try {
      $request = $this->client->request($method, $url, $this->getDefaultParameter());
      if ($request->getStatusCode() === 200) {
        $response = json_decode($request->getBody());

        foreach ($response as $group) {
          if ($group->name === $name) {
            return $group->id;
          }
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }

    return FALSE;
  }

  protected function getUserByUsername($name, $method = 'GET') {
    $url = $this->url . '/users/' . $name . '.json';

    try {
      $request = $this->client->request($method, $url, $this->getDefaultParameter());

      if ($request->getStatusCode() === 200) {
        $response = json_decode($request->getBody());
        return $response;
      }
      else {
        return FALSE;
      }
    }
    catch (RequestException $e) {
      watchdog_exception('discourse_sync', $e, $e->getMessage());
    }

    return FALSE;
  }

  protected function getCustomUserGroups($user) {
    $groups = [];

    foreach ($user->user->groups as $group) {
      if ($group->automatic === FALSE) {
        $groups[$group->id] = $group->name;
      }
    }

    return $groups;
  }

  protected function notify($message) {
    \Drupal::logger('discourse_sync')->notice($message);
    drupal_set_message($message);
  }
}
