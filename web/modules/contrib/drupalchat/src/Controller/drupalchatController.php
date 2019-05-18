<?php
/**
 * @file
 * Contains Drupal\drupalchat\Controller\drupalchatController
 */

namespace Drupal\drupalchat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\BadResponseException;
use Drupal\Core\Url;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Renderer;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\Exception;



class drupalchatController extends ControllerBase {

	public static function drupalchat_verify_access() {
    
    $user  = \Drupal::currentUser();
    // Match path if necessary.
    $page_match = FALSE;
    if (\Drupal::config('drupalchat.settings')->get('drupalchat_path_pages')) {
      // Convert path to lowercase. This allows comparison of the same path
      // with different case. Ex: /Page, /page, /PAGE.
      $pages = Unicode::strtolower(\Drupal::config('drupalchat.settings')->get('drupalchat_path_pages'));
    
      $drupalchat_path_visibility = \Drupal::config('drupalchat.settings')->get('drupalchat_path_visibility') ?: 0;
    

      if ($drupalchat_path_visibility == 2 || $drupalchat_path_visibility == 3) {
        // Convert the Drupal path to lowercase
      
        $path = Unicode::strtolower(\Drupal::service('path.current')->getPath());
      
        // Compare the lowercase internal and lowercase path alias (if any).
        $path = ltrim($path, '/');
        $page_match =  \Drupal::service('path.matcher')->matchPath($path, $pages);
      
        // When $block->visibility has a value of 2 (BLOCK_VISIBILITY_NOTLISTED),
        // the block is displayed on all pages except those listed in $block->pages.
        // When set to 3 (BLOCK_VISIBILITY_LISTED), it is displayed only on those
        // pages listed in $block->pages.
        if($drupalchat_path_visibility == 2) $page_match = !$page_match;
        if($drupalchat_path_visibility == 3) $page_match = $page_match;
        //$page_match = !(\Drupal::config('drupalchat.settings')->get('drupalchat_path_visibility') xor $page_match);
      }
      elseif (\Drupal::moduleHandler()->moduleExists('php')) {

        $page_match = php_eval(\Drupal::config('drupalchat.settings')->get('drupalchat_path_pages') ?: NULL);
      }
      else {
        $page_match = FALSE;
      }
    }
    else {
      $page_match = TRUE;
    }
    $final = FALSE;
    $final = ((((\Drupal::config('drupalchat.settings')->get('drupalchat_rel') == DRUPALCHAT_REL_AUTH) && ($user->id()==0)) || ($user->id()>0)) && $page_match && \Drupal::currentUser()->hasPermission('access drupalchat'));
    if(\Drupal::config('drupalchat.settings')->get('drupalchat_polling_method') != DRUPALCHAT_COMMERCIAL) {
      $final = $final && (drupalchatController::_drupalchat_get_sid() != -1);
    }
    return $final;
  }


  public static function _drupalchat_get_sid($create = TRUE) {

    $user = \Drupal::currentUser();

    $sid = -1;
    
    if (\Drupal::config('drupalchat.settings')->get('drupalchat_polling_method') == DRUPALCHAT_NODEJS && isset($_SESSION['nodejs_config']['authToken'])) {
      if ((!isset($_SESSION['drupalchat']) && ($user->id() <> 0 || $create)) || $_SESSION['drupalchat']) {
        $sid = $_SESSION['nodejs_config']['authToken'];
        $_SESSION['drupalchat'] = TRUE;
      }
    }
    else if(\Drupal::config('drupalchat.settings')->get('drupalchat_polling_method') == DRUPALCHAT_COMMERCIAL) {
      if($user->id() > 0) {
        return $user->id();
      }
      else {
        return false;
        //return drupalchat_get_current_guest_id();
      }
    }
    else if ($user->id() == 0 && function_exists('session_api_get_sid')) {
      $_COOKIE['drupalchat_c_session'] = time();
      $sid = session_api_get_sid($create);
    }
    elseif ($user->id() > 0) {
      if(property_exists($user,'sid')) {
        $sid = $user->sid;
      }
      else {
        $sid = '';
        $session_manager = \Drupal::service('session_manager');
        $session_id = $session_manager->getId();
        $sid = $session_id;
      }
    }
    return $sid;
  } 

  public static function drupalchat_get_random_name() {
    $path = drupal_get_path('module', 'drupalchat') . "/guest_names/drupalchat_guest_random_names.txt";
    $f_contents = file($path);
    $line = trim($f_contents[rand(0, count($f_contents) - 1)]);
    return $line;
  }

  /**
	 * {@inheritdoc}
	 * Send messages via ajax
	 */
	public static function drupalchat_send() {
		// Load the current user.
		// $user_id = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		$account = \Drupal::currentUser();
    $token_generator = \Drupal::csrfToken();
    $formToken = Html::escape($_POST['form_token']);
    $formID = Html::escape($_POST['form_id']);
    $form_token = !empty($formToken) ? $formToken : '';
    $form_id = !empty($formID) ? $formID  : '';
    if (!$token_generator->validate($form_token, $form_id)) {
      return;
    }
  
    \Drupal::database()->merge('drupalchat_msg')
      ->key(array('message_id' => Html::escape($_POST['drupalchat_message_id']), 'uid1' => ($account->id())?$account->id():'0-'.drupalchatController::_drupalchat_get_sid(), 'uid2' => Html::escape($_POST['drupalchat_uid2'])))
      ->fields(array('message' => $_POST['drupalchat_message'], 'timestamp' => time()))
      ->execute();
    

    foreach (\Drupal::moduleHandler()->getImplementations('drupalchat_send') as $module) {
        $function = $module . '_drupalchat_send';
        $function($message);
      }

    return new JsonResponse(array());

	}

  /**
   * {@inheritdoc}
   * Process and get messages
   */
  public function drupalchat_poll() {
    //global $user;
    $user = \Drupal::currentUser();

    $initial_time = time();
    $message_count = 0;


    /*if (isset($_GET['drupalchat_last_timestamp'])) {
      $last_timestamp = Html::escape($_GET['drupalchat_last_timestamp']);
    }*/
    if ($_GET['drupalchat_last_timestamp'] > 0) {
      $last_timestamp = Html::escape($_GET['drupalchat_last_timestamp']);
    }
    else {
      $last_timestamp = $initial_time;
      //$last_timestamp = 1;
    }

    $buddylist = drupalchatController::_drupalchat_buddylist($user->id());
    $buddylist_online_old = drupalchatController::_drupalchat_buddylist_online($buddylist);


    $polling_method = \Drupal::config('drupalchat.settings')->get('drupalchat_polling_method') ?: DRUPALCHAT_AJAX;
    //echo $polling_method;
    //die;

    $json['messages'] = array();

    if ($polling_method == DRUPALCHAT_AJAX) {
      drupalchatController::_drupalchat_touch_user($user->id());
      \Drupal::moduleHandler()->invokeAll('drupalchat_ajaxpoll'); // AJAX poll hook
    }
    elseif ($polling_method == DRUPALCHAT_LONGPOLL) {
      do {
        sleep(3);
        $buddylist_online = drupalchatController::_drupalchat_buddylist_online($buddylist);
        if($user->id() > 0) {
          $message_count = db_query(' SELECT COUNT(*)
                                    FROM {drupalchat_msg} m
                                    WHERE (m.uid2 IN (:uid2,\'c-0\') OR m.uid1 = :uid2)
                                    AND m.timestamp > :timestamp', array(':uid2' => $user->id(), ':timestamp' => $last_timestamp))->fetchField();
        }
        else {
          $message_count = db_query(' SELECT COUNT(*)
                                    FROM {drupalchat_msg} m
                                    WHERE (m.uid2 IN (:uid2,\'c-0\') OR m.uid1 = :uid2)
                                    AND m.timestamp > :timestamp', array(':uid2' => '0-'._drupalchat_get_sid(), ':timestamp' => $last_timestamp))->fetchField();
        }
        drupalchatController::_drupalchat_touch_user($user->id());
        \Drupal::moduleHandler()->invokeAll('drupalchat_longpoll'); // Long poll hook
      } while (((time() - $initial_time) < (ini_get('max_execution_time') - 5)) && ($message_count == 0) && (drupalchatController::_drupalchat_buddylist_diff($buddylist_online_old, $buddylist_online)));
    }
    if (($message_count > 0) || ($polling_method == DRUPALCHAT_AJAX)) {
      if($user->id() > 0) {
        $messages = db_query('SELECT m.message_id, m.uid1, m.uid2, m.message, m.timestamp FROM {drupalchat_msg} m WHERE (m.uid2 IN (:uid2,\'c-0\') OR m.uid1 = :uid2) AND m.timestamp > :timestamp ORDER BY m.timestamp ASC', array(':uid2' => $user->id(), ':timestamp' => $last_timestamp));
      }
      else {
        $messages = db_query('SELECT m.message_id, m.uid1, m.uid2, m.message, m.timestamp FROM {drupalchat_msg} m WHERE (m.uid2 IN (:uid2,\'c-0\') OR m.uid1 = :uid2) AND m.timestamp > :timestamp ORDER BY m.timestamp ASC', array(':uid2' => '0-'.drupalchatController::_drupalchat_get_sid(), ':timestamp' => $last_timestamp));
      }
      //while ($message = db_fetch_object($messages)) {
      // Drupal 7
      foreach ($messages as $message) {
        //$arr = explode("-", $message->uid1, 2);
        if(((!strpos($message->uid1,'-')) && ($message->uid1 != $user->id())) || ((strpos($message->uid1,'-')) && ($message->uid1 != '0-'.drupalchatController::_drupalchat_get_sid()))) {
          if(!strpos($message->uid1,'-')) {
            $account = \Drupal::entityManager()->getStorage('user')->load($message->uid1);
            $temp_msg = array('message' => html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => Html::escape(user_format_name($account)), 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
            if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
              $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user($account);
            }
            $json['messages'][] = $temp_msg;
          }
          else {
            $arr = explode("-", $message->uid1, 2);
            $sid = $arr[1];
            $name = db_query('SELECT name FROM {drupalchat_users} WHERE uid = :uid AND session = :sid', array(':uid' => '0', ':sid' => $sid))->fetchField();
            $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => $name, 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
            if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
              $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user(\Drupal::entityManager()->getStorage('user')->load('0'));
            }
            $json['messages'][] = $temp_msg;
          }
        }
        else {
          if(!strpos($message->uid2,'-')) {
            $account = \Drupal::entityManager()->getStorage('user')->load($message->uid2);
            $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => Html::escape(user_format_name($account)), 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
            if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
              $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user($account);
            }
            $json['messages'][] = $temp_msg;
          }
          else {
            $arr = explode("-", $message->uid2, 2);
            $sid = $arr[1];
            $name = db_query('SELECT name FROM {drupalchat_users} WHERE uid = :uid AND session = :sid', array(':uid' => '0', ':sid' => $sid))->fetchField();
            $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => $name, 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
            if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
              $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user(\Drupal::entityManager()->getStorage('user')->load('0'));
            }
            $json['messages'][] = $temp_msg;
          }
        }
        //if($message->
        if ($message->timestamp > $last_timestamp) {
          $last_timestamp = $message->timestamp;
        }
      }
    }
    $json['status'] = 0;
    $json['total_messages'] = $message_count;
    $json['last_timestamp'] = $last_timestamp;
    $json['buddylist'] = isset($buddylist_online) ? $buddylist_online : $buddylist_online_old;


    /*echo '<pre>';
    print_r($json);
    echo '</pre>';*/
    return new JsonResponse($json);
  }


  private static function _drupalchat_touch_user($uid) {
    \Drupal::database()->update('drupalchat_users')
    ->fields(array(
      'timestamp' => time(),
    ))
    ->condition('uid', $uid)
    //->condition('session', drupalchatController::_drupalchat_get_sid())
    ->execute();
  }


  private static function _drupalchat_buddylist_diff($ar1, $ar2) {
    if ($ar1['total'] != $ar2['total']) {
      return FALSE;
    }

    foreach ($ar1 as $key => $value) {
      if (!isset($ar2[$key])) {
        return FALSE;
      }
      if ($value['status'] != $ar2[$key]['status']) {
        return FALSE;
      }
    }

    return TRUE;
  }

  public static function _drupalchat_buddylist($uid) {
    $users = array();

    $drupalchat_rel = \Drupal::config('drupalchat.settings')->get('drupalchat_rel');
    if ($drupalchat_rel == DRUPALCHAT_REL_UR && function_exists('user_relationships_load')) {
      $relationships = user_relationships_load(array('name' => Tags::explode(\Drupal::config('drupalchat.settings')->get('drupalchat_ur_name')), 'user' => $uid, 'approved' => 1), array(), TRUE);
      foreach ($relationships as $rid => $relationship) {
        $uid == $relationship->requester_id ? $users[] = $relationship->requestee_id : $users[] = $relationship->requester_id;
      }
    }
    elseif ($drupalchat_rel == DRUPALCHAT_REL_FF) {
      $result = db_query("SELECT * FROM {flag_friend} WHERE uid = :uid OR friend_uid = :uid", array(':uid' => $uid, ':friend_uid' => $uid));

      foreach ($result as $friend) {
        $uid == $friend->uid ? $users[] = $friend->friend_uid : $users[] = $friend->uid;
      }
    }
    return $users;
  }


  public static function _drupalchat_get_buddylist($uid, $drupalchat_ur_name = NULL) {
    $final_list = array();
    $drupalchat_rel = \Drupal::config('drupalchat.settings')->get('drupalchat_rel') ?: DRUPALCHAT_REL_AUTH;
    if($drupalchat_ur_name == NULL) {
      $drupalchat_ur_name = \Drupal::config('drupalchat.settings')->get('drupalchat_ur_name') ?: 'friend';
    }
    if ($drupalchat_rel == DRUPALCHAT_REL_UR && function_exists('user_relationships_type_load') && function_exists('user_relationships_load')) {
    $r_names = Tags::explode($drupalchat_ur_name);
      foreach($r_names as $r_name) {
        $comp_r_name = user_relationships_type_load(array('name' => $r_name), TRUE);
        $final_list[$comp_r_name->rtid]['name'] = $comp_r_name->name;
        $final_list[$comp_r_name->rtid]['plural'] = $comp_r_name->plural_name;
        $relationships = user_relationships_load(array('rtid' => $comp_r_name->rtid, 'user' => $uid, 'approved' => 1), array(), TRUE);
        foreach ($relationships as $rid => $relationship) {
          $uid == $relationship->requester_id ? $final_list[$comp_r_name->rtid]['valid_uids'][] = $relationship->requestee_id : $final_list[$comp_r_name->rtid]['valid_uids'][] = $relationship->requester_id;
        }
      }
    }
    else if ($drupalchat_rel == DRUPALCHAT_REL_FF){
      $fid = '1';
      $final_list[$fid]['name'] = 'friend';
      $final_list[$fid]['plural'] = 'friends';
      $result = db_query("SELECT * FROM {flag_friend} WHERE uid = :uid OR friend_uid = :uid", array(':uid' => $uid, ':friend_uid' => $uid));
      foreach ($result as $friend) {
        ($uid == $friend->uid) ? $final_list[$fid]['valid_uids'][] = $friend->friend_uid : $final_list[$fid]['valid_uids'][] = $friend->uid;
      }
    }
    return $final_list;
  }

  

  public static function _drupalchat_buddylist_online($buddylist) {
    global $base_url;
    $user = \Drupal::currentUser();
    $database = \Drupal::database();

    $users = array();
    if(\Drupal::config('drupalchat.settings')->get('drupalchat_enable_chatroom') == 1) {
      $users['c-0'] = array('name' => t('Public Chatroom')->__toString(), 'status' => '1');
      if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
        $users['c-0']['p'] = $base_url . '/' . drupal_get_path('module', 'drupalchat') . '/css/themes/' . \Drupal::config('drupalchat.settings')->get('drupalchat_theme') . '/images/default_room.png';
      }
    }
    if (\Drupal::config('drupalchat.settings')->get('drupalchat_rel') > DRUPALCHAT_REL_AUTH) {
      // Return empty on an empty buddylist
      if (empty($buddylist)) {
        $users['total'] = 0;
        return $users;
      }
      $result = $database
          ->select('drupalchat_users', 'n')
          ->fields('n', ['uid', 'name', 'status'])
          ->condition('timestamp', (time() - \Drupal::config('drupalchat.settings')->get('drupalchat_user_latency')), '>=')
          ->condition('uid', $buddylist, 'IN')
          ->execute()
          ->fetchAll();
    }
    else {
      if($user->id() > 0) {
        $result = $database
          ->select('drupalchat_users', 'n')
          ->fields('n', ['uid', 'name', 'status', 'session'])
          ->condition('uid', $user->id(), '<>')
          ->condition('timestamp', (time() - \Drupal::config('drupalchat.settings')->get('drupalchat_user_latency')), '>=')
          ->execute()
          ->fetchAll();
      }
      else {
        $result = $database
          ->select('drupalchat_users', 'n')
          ->fields('n', ['uid', 'name', 'status', 'session'])
          ->condition('timestamp', (time() - \Drupal::config('drupalchat.settings')->get('drupalchat_user_latency')), '>=')
          ->condition('session', drupalchatController::_drupalchat_get_sid(), '<>')
          ->execute()
          ->fetchAll();
      }
    }
    
    foreach ($result as $buddy) {
      if($buddy->uid > 0) {
        $account = \Drupal::entityManager()->getStorage('user')->load($buddy->uid);
        $users[$buddy->uid] = array('name' => Html::escape(user_format_name($account)), 'status' => $buddy->status);
        if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
          $users[$buddy->uid]['p'] = drupalchatController::drupalchat_return_pic_url_any_user(\Drupal::entityManager()->getStorage('user')->load($buddy->uid));
        }
      }
      else {
        $users[$buddy->uid . '-' . $buddy->session] = array('name' => Html::escape($buddy->name), 'status' => $buddy->status);
        if(\Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') == 1) {
          $users[$buddy->uid . '-' . $buddy->session]['p'] = drupalchatController::drupalchat_return_pic_url_any_user(\Drupal::entityManager()->getStorage('user')->load('0'));
        }
      }
    }
    $users['total'] = count($users);
    if(\Drupal::config('drupalchat.settings')->get('drupalchat_enable_chatroom') == 1) {
      $users['total']--;
    }
    
    return $users;
  }

  /**
   * drupal_get_messages()
   */
  public static function drupalchat_get_messages() {
    if(!(\Drupal::currentUser()->hasPermission('access drupalchat own logs') || \Drupal::currentUser()->hasPermission('access drupalchat all logs'))) {
      drupal_access_denied();
    }
    else {
      $user = \Drupal::currentUser();
      if(($user->id() > 0) || (drupalchatController::_drupalchat_get_sid() != -1)) {
        $output = '';
        if (\Drupal::config('drupalchat.settings')->get('drupalchat_polling_method') == DRUPALCHAT_COMMERCIAL) {
          //global $user;
          if(!isset($_SESSION['drupalchat_switch_user'])) {
            $_SESSION['drupalchat_switch_user'] = ($user->id())?$user->id():'0-'.drupalchatController::_drupalchat_get_sid();
          }
          $data = array(
            'uid' => $_SESSION['drupalchat_switch_user'],
            'api_key' => \Drupal::config('drupalchat.settings')->get('drupalchat_external_api_key'),);

          // $data = json_encode(array(
          //   'uid' => $_SESSION['drupalchat_switch_user'],
          //   'api_key' => \Drupal::config('drupalchat.settings')->get('drupalchat_external_api_key'),));
          // $options = array(
          //   'method' => 'POST',
          //   'data' => $data,
          //   'timeout' => 15,
          //   'headers' => array('Content-Type' => 'application/json'),
          // );
          if(\Drupal::currentUser()->hasPermission('access drupalchat all logs')) {
            $f_o = drupal_get_form('drupalchat_user_entry_form');
            $output .= drupal_render($f_o)->__toString();
          }
          /*****************/

          $url = DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT . '/r/';
          $client = \Drupal::httpClient();
  
          try{
            $request = $client->post($url, [
              'verify' => false,
              'form_params' => $data
            ]);  
          }
          catch(BadResponseException $exception){
            $code = $exception->getResponse()->getStatusCode();
            $error = $exception->getResponse()->getReasonPhrase();
            $e = array(
              'code' => $code,
              'error' => $error
            );
            return $e;
          }
          catch(RequestException $exception){
            $e = array(
              'code' => $exception->getResponse()->getStatusCode(),
              'error' => $exception->getResponse()->getReasonPhrase()
            );

            return $e;
          }
          if(json_decode($request->getStatusCode()) == 200){
            $query = json_decode($request->getBody());
          }



          //$result = drupal_http_request(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT . '/r/', $options);
          //$query = json_decode($result->data);
        }
        else {
          $guid = ($user->id())?($user->id()):('0-'._drupalchat_get_sid());
          $query = db_query('SELECT u.name as name, g.uid as uid, g.message as message, g.TIMESTAMP as timestamp
                       FROM (
               SELECT uid, message, TIMESTAMP
                 FROM (
               (
                 SELECT m1.uid1 AS uid, m1.timestamp AS TIMESTAMP, m1.message AS message
                 FROM {drupalchat_msg} m1
                 INNER JOIN (
                   SELECT MAX( t1.timestamp ) AS TIMESTAMP, t1.uid1
                 FROM {drupalchat_msg} t1
                 WHERE t1.uid2 =  :uid
                 GROUP BY t1.uid1
                 ) recent ON recent.timestamp = m1.timestamp
                 AND recent.uid1 = m1.uid1
                 ORDER BY TIMESTAMP DESC
               )
               UNION (
                 SELECT m1.uid2 AS uid, m1.timestamp AS TIMESTAMP, m1.message AS message
                 FROM {drupalchat_msg} m1
                 INNER JOIN (
                   SELECT MAX( t1.timestamp ) AS TIMESTAMP, t1.uid2
                 FROM {drupalchat_msg} t1
                 WHERE t1.uid1 =  :uid
                 GROUP BY t1.uid2
                 )recent ON recent.timestamp = m1.timestamp
                 AND recent.uid2 = m1.uid2
                 ORDER BY TIMESTAMP DESC
               )
                ) AS f
              ORDER BY 3 DESC
              ) AS g INNER JOIN {drupalchat_users} u ON
              (g.uid = u.uid AND u.uid!= 0) OR (u.uid = 0 AND g.uid = CONCAT(\'0-\', u.session))
            GROUP BY uid', array(':uid' => $guid));
        }
        foreach($query as $record) {
          // @FIXME
          // l() expects a Url object, created from a route name or external URI.
          // $output .= '<div style="display:block;border-bottom: 1px solid #ccc; padding: 10px;"><div style="font-size:130%; display: inline;">' . l($record->name,'drupalchat/messages/message/' . $record->uid) . '</div><div style="float:right;color:#AAA; font-size: 70%;">' . format_date($record->timestamp,'long') . '</div><div style="display: block; padding: 10px;">' . Html::escape($record->message) . '</div></div>';

        }
        //$output .= '</tbody></table>';
        //$user_item = user_load($user->id());
        //$output .= '<pre>' . print_r($user_item,true) . '</pre>';
        //$output .= theme('user_picture', array('account' =>$user_item));
      }
      return $output;
    }
  }

  public static function drupalchat_get_messages_specific($id = "1") {
    if(!(\Drupal::currentUser()->hasPermission('access drupalchat own logs') || \Drupal::currentUser()->hasPermission('access drupalchat all logs'))) {
      drupal_access_denied();
    }
    else {
      $user = \Drupal::currentUser();
      if(($user->id() > 0) || (drupalchatController::_drupalchat_get_sid() != -1)) {
        $guid = ($user->id())?($user->id()):('0-'.drupalchatController::_drupalchat_get_sid());
        $output = '';
      
        if (\Drupal::config('drupalchat.settings')->get('drupalchat_polling_method') == DRUPALCHAT_COMMERCIAL) {
          global $user;
          if(!isset($_SESSION['drupalchat_switch_user'])) {
            $_SESSION['drupalchat_switch_user'] = ($user->id())?$user->id():'0-'.drupalchatController::_drupalchat_get_sid();
          }
          $data = array(
            'uid1' => $_SESSION['drupalchat_switch_user'],
            'uid2' => $id,
            'api_key' => \Drupal::config('drupalchat.settings')->get('drupalchat_external_api_key'),);
         
          $url = DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT . '/q/';
          $client = \Drupal::httpClient();
  
          try{
            $request = $client->post($url, [
              'verify' => false,
              'form_params' => $data
            ]);  
          }
          catch(BadResponseException $exception){
            $code = $exception->getResponse()->getStatusCode();
            $error = $exception->getResponse()->getReasonPhrase();
            $e = array(
              'code' => $code,
              'error' => $error
            );
            return $e;
          }
          catch(RequestException $exception){
            $e = array(
              'code' => $exception->getResponse()->getStatusCode(),
              'error' => $exception->getResponse()->getReasonPhrase()
            );

            return $e;
          }
          if(json_decode($request->getStatusCode()) == 200){
            $q = json_decode($request->getBody());
          }

         // $result = drupal_http_request(DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/q/', $options);
         // $q = json_decode($result->data);
        }
        else {
          $result = db_select('drupalchat_msg', 'm');
          $result->innerJoin('drupalchat_users', 'u', '(m.uid1 = u.uid AND u.uid!= 0) OR (u.uid = 0 AND m.uid1 = CONCAT(\'0-\', u.session))');
          $result->innerJoin('drupalchat_users', 'v', '(m.uid2 = v.uid AND v.uid!= 0) OR (v.uid = 0 AND m.uid2 = CONCAT(\'0-\', v.session))');
          $result->addField('u', 'name', 'from_name');
          $result->addField('v', 'name', 'to_name');
          $result->fields('m', array('uid1', 'uid2', 'message', 'timestamp'));
          $result->condition(db_or()
            ->condition(db_and()->condition('uid1', $guid)->condition('uid2',$id))
            ->condition(db_and()->condition('uid1', $id)->condition('uid2',$guid)));
          $q = $result->execute();
        }
        $oldname = NULL;
        foreach($q as $record) {
          if($oldname == $record->from_name) {
            $output .= '<div style="display: block; padding-top: 0%; padding-bottom: 0%;">' . $record->message . '</div>';
          }
          else {
            $output .= '<div style="display:block;border-bottom: 1px solid #ccc; padding: 1% 0% 1% 0%;"></div><div style="display:block; padding-top: 1%; padding-bottom: 0%"><div style="font-size:100%; display: inline;"><a href="#">' . $record->from_name . '</a></div><div style="float:right;font-size: 70%;">' . format_date($record->timestamp,'long') . '</div><div style="display: block; padding-top: 1%; padding-bottom: 0%">' . Html::escape($record->message) . '</div></div>';
          }
          $oldname = $record->from_name;
        }
        $output .= '';
      }
      return $output;
    }
  }


  public static function drupalchat_get_thread_history() {
    $user = \Drupal::currentUser();
    $json = array();
    if(isset($_POST['drupalchat_open_chat_uids'])) {
      $chat_ids = explode(',', Html::escape($_POST['drupalchat_open_chat_uids']));

      $json['messages'] = array();
      foreach($chat_ids as $chat_id) {
        $messages = '';

        if($user->id() > 0) {
          $current_uid = $user->id();
        }
        else {
          $current_uid = '0-'.drupalchatController::_drupalchat_get_sid();
        }

        if($chat_id == 'c-0') {
          $messages = db_query('SELECT m.message_id, m.uid1, m.uid2, m.message, m.timestamp FROM {drupalchat_msg} m WHERE m.uid2 = \'c-0\' ORDER BY m.timestamp DESC LIMIT 30', array(':uid1' => $current_uid, ':uid2' => $chat_id))->fetchAll();
        }
        else {
          $messages = db_query('SELECT m.message_id, m.uid1, m.uid2, m.message, m.timestamp FROM {drupalchat_msg} m WHERE (m.uid2 = :uid2 AND m.uid1 = :uid1) OR (m.uid2 = :uid1 AND m.uid1 = :uid2) ORDER BY m.timestamp DESC LIMIT 30', array(':uid1' => $current_uid, ':uid2' => $chat_id))->fetchAll();
        }

        foreach ($messages as $message) {
        //print_r($message);
          if((((!strpos($message->uid1,'-')) && ($message->uid1 != $user->id())) || ((strpos($message->uid1,'-')) && ($message->uid1 != '0-'.drupalchatController::_drupalchat_get_sid()))) || ($message->uid2 == 'c-0')) {
            if(!strpos($message->uid1,'-')) {
              $account = user_load($message->uid1);
              $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => Html::escape(user_format_name($account)), 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
              $drupalchat_user_picture = \Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') ?: 1;
              if($drupalchat_user_picture == 1) {
                $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user($account);
              }
              $json['messages'][] = $temp_msg;
            }
            else {
              $arr = explode("-", $message->uid1, 2);
              $sid = $arr[1];
              $name = db_query('SELECT name FROM {drupalchat_users} WHERE uid = :uid AND session = :sid', array(':uid' => '0', ':sid' => $sid))->fetchField();
              $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => $name, 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
              $drupalchat_user_picture = \Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') ?: 1;
              if($drupalchat_user_picture == 1) {
                $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user(user_load('0'));
              }
              $json['messages'][] = $temp_msg;
            }
          }
          else {
            if(!strpos($message->uid2,'-')) {
              $account = user_load($message->uid2);
              $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("Hi:", $message->timestamp), 'uid1' => $message->uid1, 'name' => Html::escape(user_format_name($account)), 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);

              $drupalchat_user_picture = \Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') ?: 1;
              if($drupalchat_user_picture == 1) {
                $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user($account);
              }
              $json['messages'][] = $temp_msg;
            }
            else {
              $arr = explode("-", $message->uid2, 2);
              $sid = $arr[1];
              $name = db_query('SELECT name FROM {drupalchat_users} WHERE uid = :uid AND session = :sid', array(':uid' => '0', ':sid' => $sid))->fetchField();
              $temp_msg = array('message' => Html::escape($message->message), 'timestamp' => date("H:i", $message->timestamp), 'uid1' => $message->uid1, 'name' => $name, 'uid2' => $message->uid2, 'message_id' => Html::escape($message->message_id),);
              $drupalchat_user_picture = \Drupal::config('drupalchat.settings')->get('drupalchat_user_picture') ?: 1;
              if($drupalchat_user_picture == 1) {
                $temp_msg['p'] = drupalchatController::drupalchat_return_pic_url_any_user(user_load('0'));
              }
              $json['messages'][] = $temp_msg;
            }
          }
        }
      }
      $json['messages'] = array_reverse($json['messages']);
    }
    return new JsonResponse($json);
    
  }

  



  public static function drupalchat_return_pic_url($uid = null) {
    
    $user = \Drupal::currentUser();
    if(isset($uid)){
      $u =  \Drupal\user\Entity\User::load($uid);  
    } else {
      $u = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    }
    
    return drupalchatController::drupalchat_return_pic_url_any_user($u);    
  }


  public static function drupalchat_return_pic_url_any_user($u) {
    global $base_url;
    $url = "";
    $user_picture = array(
      '#theme' => 'user_picture',
      '#account' => $u,
    );
  
    if ((!empty($u->user_picture)) && (null !== $u->user_picture->first()) && (is_object($u->user_picture->first()))) {
      $renderImage = $u->user_picture->first()->view('large');
      $user_image = \Drupal::service('renderer')->renderPlain($renderImage);
      $user_image = print_r($user_image, true);//(string)$row_user;
      $source = strip_tags($user_image, "<img>");
      $source = explode('src="', $source);
      if(isset($source[1])) {
        $source = explode('"', $source[1]);
      } else {
        $source = explode("src='", $source[0]);
        if(isset($source[1])) {
          $source = explode("'", $source[1]);
        }
        else {
          $drupalchat_theme = \Drupal::config('drupalchat.settings')->get('drupalchat_theme') ?: 'light';
          $source[0] = $base_url . '/' . drupal_get_path('module', 'drupalchat') . '/css/themes/'. $drupalchat_theme.'/images/default_avatar.png';

        }
      }
      $url = $source[0];
      $pos = strpos($url, ':');
      if($pos !== false) {
        $url = substr($url, $pos+1);
      }

    } else { // no image set.  
        $drupalchat_theme = \Drupal::config('drupalchat.settings')->get('drupalchat_theme') ?: 'light';
        $source = $base_url . '/' . drupal_get_path('module', 'drupalchat') . '/css/themes/'. $drupalchat_theme.'/images/default_avatar.png';
        $url = $source;
        $pos = strpos($url, ':');
        if($pos !== false) {
          $url = substr($url, $pos+1);
        }
    }

    return $url;
  }

  public static function drupalchat_return_profile_url() {
    $user = \Drupal::currentUser();
    $link = 'javascript:void(0)';
    if($user->isAuthenticated()) {
      $link =  \Drupal\Core\Url::fromUserInput('/user/' . $user->id(), array('absolute' => TRUE))->toString();
    }
    return $link;
  }

  private static function _drupalchat_get_friends($uid){
    $friends = array();
    //hook to filter friends
    \Drupal::moduleHandler()->alter('drupalchat_get_friends', $friends, $uid);

    return $friends;
  }

  /**
   * Returns the groups to be used for filtering users
   */
  private static function _drupalchat_get_groups($uid) {

    $groups = array();

    if(function_exists('og_get_groups_by_user')) {
      $og_groups = og_get_groups_by_user();
      if(isset($og_groups['node'])) {
        $groups = $og_groups['node'];
      }
    }

    //hook to filter groups
    \Drupal::moduleHandler()->alter('drupalchat_get_groups', $groups, $uid);
    return $groups;
  }


  public static function _drupalchat_get_user_details(){
    $user = \Drupal::currentUser();
    $user_id = $user->id();
    $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

    if (\Drupal::currentUser()->hasPermission('administer drupalchat')) {
        $chat_role = "admin";
    } else if (\Drupal::currentUser()->hasPermission('moderate drupalchat')) {
        $chat_role = "moderator";
    } else {
        $chat_role = "participant";
    }

    $hook_user_name = '';
    $hook_user_avatar_url = '';
    $hook_user_profile_url = '';
    $hook_user_roles = array();
    \Drupal::moduleHandler()->alter('drupalchat_get_username', $hook_user_name, $user_id);
    \Drupal::moduleHandler()->alter('drupalchat_get_user_avatar_url', $hook_user_avatar_url, $user_id);
    \Drupal::moduleHandler()->alter('drupalchat_get_user_profile_url', $hook_user_profile_url, $user_id);
    \Drupal::moduleHandler()->alter('drupalchat_get_user_roles', $hook_user_roles, $user_id);

    $userSiteRoles = user_role_names();
    // if(\Drupal::currentUser()->hasPermission('administer drupalchat')) {
    //   $role = "admin";
    // }
    // else {
    //   $role = array(); 
    //   $userRole = \Drupal::currentUser()->getRoles();
    //   //print_r(gettype($roles));
    //   for($index = 0; $index < sizeof($userRole); $index++){
    //     $role[(string)$userRole[$index]] = $userSiteRoles[$userRole[$index]];
    //   }
    //   // print_r($role);
    // }

    if(!empty($hook_user_roles)){
      $role = $hook_user_roles;
    }else{
      // $role = $user->roles;
      if(\Drupal::currentUser()->hasPermission('administer drupalchat')) {
        $role = "admin";
      }
      else {
        $role = array(); 
        $userRole = \Drupal::currentUser()->getRoles();
        //print_r(gettype($roles));
        for($index = 0; $index < sizeof($userRole); $index++){
          $role[(string)$userRole[$index]] = $userSiteRoles[$userRole[$index]];
        }
        // print_r($role);
      }
    }

    if(!empty($hook_user_name)){
      $user_name = $hook_user_name;
    }else{
      $user_name = Html::escape(user_format_name($account));
    }

    $data = array(
      'user_name' => $user_name,
      'user_id' => $user_id,
      'user_roles' => $role,
      'chat_role' => $chat_role,
      'user_status' => '1',
      'user_list_filter' => 'all',
    );

    if ($chat_role == 'admin' || $chat_role == 'moderator') {
        $data['user_site_roles'] = $userSiteRoles;
    }
    

    if(!empty($hook_user_avatar_url)){
      $data['user_avatar_url'] = $hook_user_avatar_url;
    }else{
      $data['user_avatar_url'] = drupalchatController::drupalchat_return_pic_url();
    }
    
    if(!empty($hook_user_profile_url)){
      $data['user_profile_url'] = $hook_user_profile_url;
    }else{
      $data['user_profile_url'] = drupalchatController::drupalchat_return_profile_url();
    }
    
    $hook_user_friends = array();
    $hook_user_groups = array();
    \Drupal::moduleHandler()->alter('drupalchat_get_groups', $hook_user_groups, $user_id);
    \Drupal::moduleHandler()->alter('drupalchat_get_friends', $hook_user_friends, $user_id);

    $drupalchat_rel = \Drupal::config('drupalchat.settings')->get('drupalchat_rel') ?: DRUPALCHAT_REL_AUTH;
    if ($drupalchat_rel == DRUPALCHAT_REL_OG) {
        $user_groups = drupalchatController::_drupalchat_get_groups($user->uid);
        if (!empty($user_groups)) {
          $data['user_list_filter'] = 'group';
          $data['user_groups'] = $user_groups;
        }
    } else if ($drupalchat_rel > DRUPALCHAT_REL_AUTH) {
        $data['user_list_filter'] = 'friend';
        $new_valid_uids = drupalchatController::_drupalchat_get_buddylist($user->uid);

        if (!isset($_SESSION['drupalchat_user_roster_list']) || ($_SESSION['drupalchat_user_roster_list'] != $new_valid_uids)) {
            $data['user_relationships'] = $new_valid_uids;
            $_SESSION['drupalchat_user_roster_list'] = $new_valid_uids;
        } else {
            $data['user_relationships'] = $new_valid_uids;
        }
    } else if (!empty($hook_user_friends)) {
      $data['user_list_filter'] = 'friend';
      $final_list = array();
      $final_list['1']['name'] = 'friend';
      $final_list['1']['plural'] = 'friends';
      $final_list['1']['valid_uids'] = $hook_user_friends;
      $data['user_relationships'] = $final_list;
    } else if (!empty($hook_user_groups)){
        $data['user_list_filter'] = 'group';
        $data['user_groups'] = $hook_user_groups;
    }

    return $data;

  }


	public static function _drupalchat_get_auth($formValues) {
  	
  	// Load the current user.
		$user = \Drupal::currentUser();

    // check if the auth request is from the setttings page.
    if(array_key_exists('api_key', $formValues)){
      $api_key = $formValues['api_key'];
    }else{
      $api_key_from_database = \Drupal::config('drupalchat.settings')->get('drupalchat_external_api_key') ?: NULL;
      $api_key = trim($api_key_from_database);
    }

    if(array_key_exists('app_id', $formValues)){
      $app_id = $formValues['app_id'];
    }else{
      $app_id_from_database = \Drupal::config('drupalchat.settings')->get('drupalchat_app_id') ?: NULL;
      $app_id = trim($app_id_from_database);
    }
  	

    $data = array(
     	'api_key' => $api_key,
      'app_id' => $app_id,
      'version' => 'D8-8.1.0',
    );

    $user_data = drupalchatController::_drupalchat_get_user_details();
    $data = array_merge($data, $user_data);
    $_SESSION['user_data'] = json_encode($user_data);
   

    $url = DRUPALCHAT_EXTERNAL_A_HOST . ':' . DRUPALCHAT_EXTERNAL_A_PORT .  '/api/1.1/token/generate';
    $client = \Drupal::httpClient();

    // print_r($data);
    
    try{
      $request = $client->post($url, [
        'verify' => false,
        'form_params' => $data,
      ]);
       
    }
    catch(BadResponseException $exception){
      $code = $exception->getResponse()->getStatusCode();
      $error = $exception->getResponse()->getReasonPhrase();
      $e = array(
        'code' => $code,
        'error' => $error
      );
      return $e;
    }
    catch(RequestException $exception){
      $e = array(
        'code' => $exception->getResponse()->getStatusCode(),
        'error' => $exception->getResponse()->getReasonPhrase()
      );

      return $e;
    }
    if(json_decode($request->getStatusCode()) == 200){
      $response = json_decode($request->getBody());
      if($user->isAuthenticated()){
        $_SESSION['token'] = $response->key;
      }
      if(array_key_exists('app_id', $response)){
        \Drupal::configFactory()->getEditable('drupalchat.settings')->set('drupalchat_app_id', $response->app_id)->save();
      }
      return $response;
    }

	}



	public function ex_auth() {
		
		// Load the current user.
		$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		$user_name = $user->getUsername();
    $uid= $user->get('uid')->value;
		if($uid){
      $response = drupalchatController::_drupalchat_get_auth(array());     
		}
		return new JsonResponse($response);
	}

  /**
   * Function to render drupalchat app settings iframe.
   */
  public function drupalchat_app_settings(){
    // Load the current user.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $user_name = $user->getUsername();

    if(isset($_SESSION['token']) && !empty($_SESSION['token'])){
      $token = $_SESSION['token'];
    }else{
      $json = drupalchatController::_drupalchat_get_auth(array());
      $token = $json->key;
    }

    $drupalchat_host = DRUPALCHAT_EXTERNAL_A_HOST;
    $host = explode("/", $drupalchat_host);
    $host_name = $host[2];

    $dashboardUrl = "//".DRUPALCHAT_EXTERNAL_CDN_HOST."/apps/dashboard/#/app-settings?sessid=". $token ."&hostName=". $host_name ."&hostPort=".DRUPALCHAT_EXTERNAL_A_PORT;

    // return array(
    //   '#markup' => '<iframe id="app_settings" width="90%" height="800px" border="none" src = "'.$dashboardUrl.'"></iframe>',
    //   '#allowed_tags' => ['iframe'],
    // );

    $form = array();
    $form['drupalchat_app_dashboard'] = array(
      '#type' => 'button',
      '#attributes' => array('onClick' => 'window.open("'.$dashboardUrl.'","_blank")'),
      '#value' => t('Click here to open App Dashboard'),
    );

    return $form;
    
  }


  public static function _drupalchat_chat() {
    $user = \Drupal::currentUser();

    $chat = array();
    $chat['name'] = 'chat';
    $chat['header'] = t('Chat')->__toString();

    $buddylist = drupalchatController::_drupalchat_buddylist($user->id());
    $buddylist_online = drupalchatController::_drupalchat_buddylist_online($buddylist);


    //JON COMMENTS
    $chat['contents'] = '<div class="chat_options">' . '<a class="chat_loading" href="#"></a>'. '</div>';
    
    $items = array();
    foreach ($buddylist_online as $key => $value) {
      if ($key != 'total') {
        $items[] = array('#markup' => '<a class="' . $key . '" href="#">' . $value['name'] . '</a>', '#wrapper_attributes' => array('class' =>'status-' . $value['status'],));
      }
    }
    
    if ($items) {
      $item_list = array(
        '#theme' => 'item_list',
        '#items' => $items,
        '#list_type' => 'ul'
      );
      
      $chat['footer'] = $item_list;
    }
    else {
      $no_user_item = array();
      $no_user_item[] = array(
        '#markup' => \Drupal::l(t('No users online')->__toString(), Url::fromRoute('drupalchat.user')),
        '#wrapper_attributes' => array('class' => 'link'),
      );
      $render_array = array(
        '#theme' => 'item_list',
        '#items' => $no_user_item,
        '#list_type' => 'ul',
      );
      $chat['footer'] = $render_array;
    }
    $chat['text'] = t('Chat')->__toString() . ' (<span class="online-count">' . count($items) . '</span>)';
    //$chat['text'] = t('Chat') . ' (<span class="online-count">' . count($items) . '</span>)';

    $theme = \Drupal::config('drupalchat.settings')->get('drupalchat_theme') ?: 'light';
    $image = [
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'drupalchat') . '/css/themes/' . $theme . '/images/loading.gif',
      '#width' => NULL,
      '#height' => NULL,
      '#alt' => t('chat')->__toString(),
      '#attributes' => array('class' => 'icon')
    ];
    
    $chat['icon'] = $image;

    $drupalchat_subpanel = array(
      '#theme' => 'drupalchat_subpanel',
      '#subpanel' => $chat
    );

    return $drupalchat_subpanel;
  }


  /**
   * Implements autocomplete feature for UR Integration.
   */
  public function _drupalchat_ur_autocomplete($string) {
    $array = Tags::explode($string);
    // Fetch last value

    $last_string =  Unicode::strtolower(array_pop($array));
    $matches = array();
    $query = db_select('user_relationship_types', 'u');
    // Select rows that match the string
    $return = $query
      ->fields('u', array('name'))
      ->condition('u.name', '%' . db_like($last_string) . '%', 'LIKE')
      ->range(0, 10)
      ->execute();
    $prefix = count($array) ? Tags::implode($array) . ', ' : '';
    // add matches to $matches
    foreach ($return as $row) {
      if(!in_array($row->name, $array))
      $matches[$prefix . $row->name] = Html::escape($row->name);
    }

    // return for JS
    return new JsonResponse($matches);
  }


}




