<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class OnpbxCalls extends ControllerBase {

  /**
   * Get Calls.
   */
  public static function getCalls($params) {
    $start = strtotime("today -9 day");
    $end = strtotime("tomorrow");
    if (isset($params['start'])) {
      $start = $params['start'];
    }
    if (isset($params['end'])) {
      $end = $params['end'];
    }
    if ($g_start = \Drupal::request()->query->get('start')) {
      $start = strtotime($g_start);
    }
    if ($g_end = \Drupal::request()->query->get('end')) {
      $end = strtotime("$g_end + 1day");
    }
    $post = [
      "date_from" => (new \DateTime("@$start"))->format("r"),
      "date_to" => (new \DateTime("@$end"))->format("r"),
    ];
    $valid = [
      'type',
      'billsec_to',
    ];
    foreach ($valid as $k => $v) {
      if (isset($params[$v])) {
        $post[$v] = $params[$v];
      }
    }

    $request = Api::request("history/search.json", $post);
    $calls = FALSE;
    if ($raw = Api::isOk($request)) {
      $limit = \Drupal::config('onlinepbx.settings')->get('limit');
      $data = self::sort($raw, $limit);
      $calls = self::filter($data, $params, $limit);
      $calls['days'] = Period::days($start, $end);
      $calls['time'] = Period::dayTime();
    }

    return $calls;
  }

  /**
   * Filter.
   */
  public static function filter($raw, $params = [], $limit = 15) {
    $result = [
      'calls' => [],
      'users' => [],
      'gateways' => [],
      'clietns' => [],
    ];
    foreach ($raw['calls'] as $key => $call) {
      if (self::checkParams($call, $params)) {
        $result['calls'][] = $call;
        $result['clietns'][$call['client']]['name'] = "";
        $result['clietns'][$call['client']]['calls'][] = $call;
        $user = $call['user'];
        $gateway = $call['gw'];
        if (!isset($result['gateways'][$gateway])) {
          $result['gateways'][$gateway] = [
            'id' => $gateway,
            'name' => $call['gwname'],
            'picup' => 0,
            'calls' => 0,
          ];
        }
        if (!isset($result['users'][$user])) {
          $result['users'][$user] = [
            'id' => $user,
            'name' => $call['uname'],
            'picup' => 0,
            'calls' => 0,
          ];
        }
        $result['gateways'][$gateway]['picup'] = $result['gateways'][$gateway]['picup'] + 1;
        $result['users'][$user]['picup'] = $result['users'][$user]['picup'] + 1;
        if ($call['bsec'] > $limit) {
          $result['gateways'][$gateway]['calls'] = $result['gateways'][$gateway]['calls'] + 1;
          $result['users'][$user]['calls'] = $result['users'][$user]['calls'] + 1;
        }
        $usr = $result['users'][$user];
        $result['users'][$user]['xname'] = "{$usr['name']}[{$usr['calls']}/{$usr['picup']}]";
        $gw = $result['gateways'][$gateway];
        $result['gateways'][$gateway]['xname'] = "{$gw['name']}[{$gw['calls']}/{$gw['picup']}]";
      }
    }
    $result['users'] = self::sortColls($result['users']);
    $result['gateways'] = self::sortColls($result['gateways']);
    return $result;
  }

  /**
   * CollSort.
   */
  public static function sortColls($data) {
    foreach ($data as $key => $row) {
      $calls[$key] = $row['calls'];
      $picup[$key] = $row['picup'];
    }
    array_multisort($calls, SORT_DESC, $picup, SORT_DESC, $data);
    $result = [];
    foreach ($data as $key => $value) {
      $id = $value['id'];
      $result[$id] = $value;
    }
    return $result;
  }

  /**
   * Filter.
   */
  public static function checkParams($call, $params) {
    $check = TRUE;
    if ($check && isset($params['type'])) {
      $check = FALSE;
      if ($params['type'] == $call['type']) {
        $check = TRUE;
      }
    }
    if ($check && isset($params['bsec'])) {
      $check = FALSE;
      if ($params['bsec']) {
        if ($call['bsec'] > $params['bsec']) {
          $check = TRUE;
        }
      }
      elseif (!$call['bsec']) {
        $check = TRUE;
      }
    }
    if ($check && $clients = \Drupal::request()->query->get('clients')) {
      $check = FALSE;
      $clientsok = [];
      foreach (explode(" ", $clients) as $client) {
        $clientsok[] = self::phoneNormalize($client);
      }
      if (in_array($call['client'], $clientsok)) {
        $check = TRUE;
      }
    }
    if ($check && $users = \Drupal::request()->query->get('users')) {
      $check = FALSE;
      if (in_array($call['user'], explode(" ", $users))) {
        $check = TRUE;
      }
    }
    if ($check && $gateways = \Drupal::request()->query->get('gateways')) {
      $check = FALSE;
      if (in_array($call['gw'], explode(" ", $gateways))) {
        $check = TRUE;
      }
    }
    return $check;
  }

  /**
   * Sort (разбить по группам).
   */
  public static function sort($calls, $limit = 15) {
    $result = [];
    foreach ($calls as $call) {
      $date = $call['date'];
      $type = FALSE;
      if ($call['type'] == 'inbound') {
        $user   = $call['to'];
        $client = $call['caller'];
      }
      elseif ($call['type'] == 'outbound') {
        $user   = $call['caller'];
        $client = $call['to'];
      }
      $gateway = substr($call['gateway'], -10);
      if ($gateway && in_array($call['type'], ['inbound', 'outbound'])) {
        $client = self::phoneNormalize($client);
        if (strlen($client) > 8) {
          $msg = str_replace("_", " ", $call['hangup_cause']);
          $call_ready = [
            'type'   => $call['type'],
            'user'   => $user,
            'uname'  => OnpbxUsers::userName($user),
            'client' => $client,
            'gw'     => $gateway,
            'gwname' => OnpbxGateways::gatewayName($gateway),
            'date'   => $date,
            'day'    => format_date($date, 'custom', 'j M'),
            'time'   => Period::timePeriod($date),
            'dur'    => $call['duration'],
            'bsec'   => $call['billsec'],
            'start'  => $call['duration'] - $call['billsec'],
            'msg'    => strtolower($msg),
            'uuid'   => $call['uuid'],
          ];
          $result['calls'][] = $call_ready;
          if (!isset($result['gateways'][$gateway])) {
            $result['gateways'][$gateway] = [
              'name' => $call_ready['gwname'],
              'picup' => 0,
              'calls' => 0,
            ];
          }
          if (!isset($result['users'][$user])) {
            $result['users'][$user] = [
              'name' => $call_ready['uname'],
              'picup' => 0,
              'calls' => 0,
            ];
          }
          $result['gateways'][$gateway]['picup'] = $result['gateways'][$gateway]['picup'] + 1;
          $result['users'][$user]['picup'] = $result['users'][$user]['picup'] + 1;
          if ($call['billsec'] > $limit) {
            $result['gateways'][$gateway]['calls'] = $result['gateways'][$gateway]['calls'] + 1;
            $result['users'][$user]['calls'] = $result['users'][$user]['calls'] + 1;
          }
          $usr = $result['users'][$user];
          $result['users'][$user]['xname'] = "{$usr['name']}[{$usr['calls']}/{$usr['picup']}]";
          $gw = $result['gateways'][$gateway];
          $result['gateways'][$gateway]['xname'] = "{$gw['name']}[{$gw['calls']}/{$gw['picup']}]";
        }
      }

    }

    return $result;
  }

  /**
   * Normailze.
   */
  public static function phoneNormalize($phone) {
    $phone = str_replace("+", "", $phone);
    $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
    if (strlen($phone) == 11) {
      if (substr($phone, 0, 1) == 8) {
        $phone = "7" . substr($phone, 1);
      }
      if (substr($phone, 0, 4) == '7800') {
        $phone = FALSE;
      }
    }
    else {
      $phone = FALSE;
    }
    return $phone;
  }

}
