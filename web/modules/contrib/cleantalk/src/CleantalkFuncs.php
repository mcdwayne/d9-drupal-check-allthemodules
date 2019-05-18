<?php

namespace Drupal\cleantalk;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Cleantalk class create request
 */

class CleantalkFuncs {

	/*
	 * get form submit_time
	*/

	static public function _cleantalk_get_submit_time()	{

		return self::_cleantalk_apbct_cookies_test() == 1 ? time() - (int)\Drupal::request()->cookies->get('apbct_timestamp') : null;

	}

	/**
	* Cookie test
	* @return int
	*/  

	static public function _cleantalk_apbct_cookies_test() {   

	    if (\Drupal::request()->cookies->get('apbct_cookies_test')) {
	        
	        $cookie_test = json_decode(stripslashes(\Drupal::request()->cookies->get('apbct_cookies_test')), true);	        
	        $check_srting = \Drupal::config('cleantalk.settings')->get('cleantalk_authkey');

	        foreach ($cookie_test['cookies_names'] as $cookie_name) {

	            $check_srting .= \Drupal::request()->cookies->get($cookie_name);

	        } 

	        unset($cokie_name);
	        
	        if ($cookie_test['check_value'] == md5($check_srting)) {

	            return 1;

	        }

	        else {

	            return 0;

	        }

	    }

	    else {

	        return null;

	    }

	}

	/*
	* Get data from submit recursively
	*/

	static public function _cleantalk_get_fields_any($arr, $message=array(), $email = null, $nickname = array('nick' => '', 'first' => '', 'last' => ''), $subject = null, $contact = true, $prev_name = '')
	{
	  //Skip request if fields exists
	  $skip_params = array(
	      'ipn_track_id',   // PayPal IPN #
	      'txn_type',     // PayPal transaction type
	      'payment_status',   // PayPal payment status
	      'ccbill_ipn',     // CCBill IPN 
	    'ct_checkjs',     // skip ct_checkjs field
	    'api_mode',         // DigiStore-API
	    'loadLastCommentId', // Plugin: WP Discuz. ticket_id=5571
	    );
	  
	  // Fields to replace with ****
	    $obfuscate_params = array(
	        'password',
	        'pass',
	        'pwd',
	    'pswd'
	    );
	  
	  // Skip feilds with these strings and known service fields
	  $skip_fields_with_strings = array( 
	    // Common
	    'ct_checkjs', //Do not send ct_checkjs
	    'nonce', //nonce for strings such as 'rsvp_nonce_name'
	    'security',
	    // 'action',
	    'http_referer',
	    'timestamp',
	    'captcha',
	    // Formidable Form
	    'form_key',
	    'submit_entry',
	    // Custom Contact Forms
	    'form_id',
	    'ccf_form',
	    'form_page',
	    // Qu Forms
	    'iphorm_uid',
	    'form_url',
	    'post_id',
	    'iphorm_ajax',
	    'iphorm_id',
	    // Fast SecureContact Froms
	    'fs_postonce_1',
	    'fscf_submitted',
	    'mailto_id',
	    'si_contact_action',
	    // Ninja Forms
	    'formData_id',
	    'formData_settings',
	    'formData_fields_\d+_id',
	    'formData_fields_\d+_files.*',    
	    // E_signature
	    'recipient_signature',
	    'output_\d+_\w{0,2}',
	    // Contact Form by Web-Settler protection
	        '_formId',
	        '_returnLink',
	    // Social login and more
	    '_save',
	    '_facebook',
	    '_social',
	    'user_login-',
	    // Contact Form 7
	    '_wpcf7',
	    'avatar__file_image_data',
	  );
	    $fields_exclusions = CleantalkCustomConfig::get_fields_exclusions();
	    if ($fields_exclusions)
	        array_merge($skip_fields_with_strings,$fields_exclusions);  
	  // Reset $message if we have a sign-up data
	    $skip_message_post = array(
	        'edd_action', // Easy Digital Downloads
	    );
	  
	    foreach($skip_params as $value){
	      if(@array_key_exists($value,\Drupal::request()->query->all())||@array_key_exists($value,\Drupal::request()->request->all()))
	        $contact = false;
	    } unset($value);
	    
	  if(count($arr)){
	    foreach($arr as $key => $value){
	      
	      if(gettype($value)=='string'){
	        $decoded_json_value = json_decode($value, true);
	        if($decoded_json_value !== null)
	          $value = $decoded_json_value;
	      }
	      
	      if(!is_array($value) && !is_object($value)){
	        
	        if (in_array($key, $skip_params, true) && $key != 0 && $key != '' || preg_match("/^ct_checkjs/", $key))
	          $contact = false;
	        
	        if($value === '')
	          continue;
	        
	        // Skipping fields names with strings from (array)skip_fields_with_strings
	        foreach($skip_fields_with_strings as $needle){
	          if (preg_match("/".$needle."/", $prev_name.$key) == 1){
	            continue(2);
	          }
	        }unset($needle);
	        
	        // Obfuscating params
	        foreach($obfuscate_params as $needle){
	          if (strpos($key, $needle) !== false){
	            $value = self::_cleantalk_obfuscate_param($value);
	            continue(2);
	          }
	        }unset($needle);
	        

	        // Decodes URL-encoded data to string.
	        $value = urldecode($value); 

	        // Email
	        if (!$email && preg_match("/^\S+@\S+\.\S+$/", $value)){
	          $email = $value;
	          
	        // Names
	        }elseif (preg_match("/name/i", $key)){
	          
	          preg_match("/((name.?)?(your|first|for)(.?name)?)$/", $key, $match_forename);
	          preg_match("/((name.?)?(last|family|second|sur)(.?name)?)$/", $key, $match_surname);
	          preg_match("/^(name.?)?(nick|user)(.?name)?$/", $key, $match_nickname);
	          
	          if(count($match_forename) > 1)
	            $nickname['first'] = $value;
	          elseif(count($match_surname) > 1)
	            $nickname['last'] = $value;
	          elseif(count($match_nickname) > 1)
	            $nickname['nick'] = $value;
	          else
	            $message[$prev_name.$key] = $value;
	        
	        // Subject
	        }elseif ($subject === null && preg_match("/subject/i", $key)){
	          $subject = $value;
	        
	        // Message
	        }else{
	          $message[$prev_name.$key] = $value;         
	        }
	        
	      }elseif(!is_object($value)){
	        
	        $prev_name_original = $prev_name;
	        $prev_name = ($prev_name === '' ? $key.'_' : $prev_name.$key.'_');
	        
	        $temp = self::_cleantalk_get_fields_any($value, $message, $email, $nickname, $subject, $contact, $prev_name);
	        
	        $message  = $temp['message'];
	        $email    = ($temp['email']     ? $temp['email'] : null);
	        $nickname   = ($temp['nickname']  ? $temp['nickname'] : null);        
	        $subject  = ($temp['subject']   ? $temp['subject'] : null);
	        if($contact === true)
	          $contact = ($temp['contact'] === false ? false : true);
	        $prev_name  = $prev_name_original;
	      }
	    } unset($key, $value);
	  }
	  
	    foreach ($skip_message_post as $v) {
	        if (\Drupal::request()->request->get($v)) {
	            $message = null;
	            break;
	        }
	    } unset($v);
	  
	  //If top iteration, returns compiled name field. Example: "Nickname Firtsname Lastname".
	  if($prev_name === ''){
	    if(!empty($nickname)){
	      $nickname_str = '';
	      foreach($nickname as $value){
	        $nickname_str .= ($value ? $value." " : "");
	      }unset($value);
	    }
	    $nickname = $nickname_str;
	  }
	  
	    $return_param = array(
	    'email'   => $email,
	    'nickname'  => $nickname,
	    'subject'   => $subject,
	    'contact'   => $contact,
	    'message'   => $message
	  );  
	  return $return_param;

	}

	/**
	* Masks a value with asterisks (*) Needed by the getFieldsAny()
	* @return string
	*/

	static public function _cleantalk_obfuscate_param($value = null) {

		if ($value && (!is_object($value) || !is_array($value))) {

			$length = strlen($value);
			$value = str_repeat('*', $length);

	  }

		return $value;

	}

	/**
	 * Cleantalk inner function - show error message and exit.
	 */
	 
	static public function _cleantalk_die($message) {

		$output = '<!DOCTYPE html><!-- Ticket #11289, IE bug fix: always pad the error page with enough characters such that it is greater than 512 bytes, even after gzip compression abcdefghijklmnopqrstuvwxyz1234567890aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz11223344556677889900abacbcbdcdcededfefegfgfhghgihihjijikjkjlklkmlmlnmnmononpopoqpqprqrqsrsrtstsubcbcdcdedefefgfabcadefbghicjkldmnoepqrfstugvwxhyz1i234j567k890laabmbccnddeoeffpgghqhiirjjksklltmmnunoovppqwqrrxsstytuuzvvw0wxx1yyz2z113223434455666777889890091abc2def3ghi4jkl5mno6pqr7stu8vwx9yz11aab2bcc3dd4ee5ff6gg7hh8ii9j0jk1kl2lmm3nnoo4p5pq6qrr7ss8tt9uuvv0wwx1x2yyzz13aba4cbcb5dcdc6dedfef8egf9gfh0ghg1ihi2hji3jik4jkj5lkl6kml7mln8mnm9ono--><html xmlns="http://www.w3.org/1999/xhtml" lang="en-US"><head>    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />    <title>Blacklisted</title>    <style type="text/css">        html {            background: #f1f1f1;        }        body {            background: #fff;            color: #444;            font-family: "Open Sans", sans-serif;            margin: 2em auto;            padding: 1em 2em;            max-width: 700px;            -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);            box-shadow: 0 1px 3px rgba(0,0,0,0.13);        }        h1 {            border-bottom: 1px solid #dadada;            clear: both;            color: #666;            font: 24px "Open Sans", sans-serif;            margin: 30px 0 0 0;            padding: 0;            padding-bottom: 7px;        }        #error-page {            margin-top: 50px;        }        #error-page p {            font-size: 14px;            line-height: 1.5;            margin: 25px 0 20px;        }        a {            color: #21759B;            text-decoration: none;        }        a:hover {            color: #D54E21;        }            </style></head><body id="error-page">    <p><center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> Spam protection</center><br><br>'.$message.'<script>setTimeout("history.back()", 5000);</script></p><p><a href="javascript:history.back()">&laquo; Back</a></p></body></html>';
		throw new ServiceUnavailableHttpException(3, $output);

	}

	/**
	 * Cleantalk inner function - gets JavaScript checking value.
	 */

	static public function _cleantalk_get_checkjs_value() {

		return md5(\Drupal::config('cleantalk.settings')->get("cleantalk_authkey") . '+' . \Drupal::config('system.site')->get("mail"));

	}

	/**
	 * Cleantalk inner function - performs antispam checking.
	 */

	static public function _cleantalk_check_spam($spam_check, $form_errors) {

		global $cleantalk_executed;

		$url_exclusion = CleantalkCustomConfig::get_url_exclusions();

		if ($url_exclusion) {

			foreach ($url_exclusion as $key=>$value) {

				if (strpos(\Drupal::request()->server->get('REQUEST_URI'),$value) !== false) {

					return; 

				}

			}
		}

		$curr_user = \Drupal::currentUser();

		if ($curr_user->hasPermission('access administration menu') || $cleantalk_executed) {

			return;

		} 

		if ($curr_user->id()) {

			$user = \Drupal\user\Entity\User::load($curr_user->id());

			// Don't check reged user with >= 'cleantalk_check_comments_min_approved' approved msgs.

			if (is_object($user) && $user->get('uid')->value > 0 && \Drupal::service('module_handler')->moduleExists('comment')) {

				$result = \Drupal::database()->query('SELECT count(*) AS count FROM {comment_field_data} WHERE uid=:uid AND status=1', array(':uid' => $user->get('uid')->value));
				$count = intval($result->fetchObject()->count);
				$ct_comments = \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments_min_approved');

				if ($count >= $ct_comments) {

					return;

				}

			}

		}

		$ct_authkey = \Drupal::config('cleantalk.settings')->get('cleantalk_authkey');
		$ct_ws = self::_cleantalk_get_ws();
		$checkjs = 0;

		if (!\Drupal::request()->cookies->get('apbct_check_js')) {

			$checkjs = NULL;

		}

		elseif (\Drupal::request()->cookies->get('apbct_check_js') == self::_cleantalk_get_checkjs_value()) {

			$checkjs = 1;

		}

		else {

			$checkjs = 0;

		}

		$ct = new Cleantalk();
		$ct->work_url = $ct_ws['work_url'];
		$ct->server_url = $ct_ws['server_url'];
		$ct->server_ttl = $ct_ws['server_ttl'];
		$ct->server_changed = $ct_ws['server_changed'];
		$ct_options=Array(
			'access_key' => $ct_authkey, 
			'cleantalk_check_comments' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments'),
			'cleantalk_check_comments_automod' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments_automod'),
			'cleantalk_check_comments_min_approved' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_comments_min_approved'),
			'cleantalk_check_register' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_register'),
			'cleantalk_check_webforms' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_webforms'),
			'cleantalk_check_contact_forms' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_contact_forms'),
			'cleantalk_check_ccf' => \Drupal::config('cleantalk.settings')->get('cleantalk_check_ccf'),
			'cleantalk_sfw' => \Drupal::config('cleantalk.settings')->get('cleantalk_sfw'),
			'cleantalk_link' => \Drupal::config('cleantalk.settings')->get('cleantalk_link'),
		);

		$sender_info = \Drupal\Component\Serialization\Json::encode(
			array(
			'cms_lang' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
			'REFFERRER' => \Drupal::request()->server->get('HTTP_REFERER'),
			'page_url'  => \Drupal::request()->server->get('SERVER_NAME') . \Drupal::request()->server->get('REQUEST_URI'),
			'USER_AGENT' => \Drupal::request()->server->get('HTTP_USER_AGENT'),
			'ct_options' => \Drupal\Component\Serialization\Json::encode($ct_options),
			'REFFERRER_PREVIOUS' => \Drupal::request()->cookies->get('apbct_prev_referer'),
			'cookies_enabled' => self::_cleantalk_apbct_cookies_test(),
			'fields_number' => sizeof($spam_check),
			'js_timezone' => \Drupal::request()->cookies->get('apbct_timezone'),
			'mouse_cursor_positions' => json_decode(\Drupal::request()->cookies->get('apbct_pointer_data')),
			'key_press_timestamp' => \Drupal::request()->cookies->get('apbct_fkp_timestamp'),
			'page_set_timestamp' => \Drupal::request()->cookies->get('apbct_ps_timestamp'),
            'form_validation' => ($form_errors && is_array($form_errors)) ? json_encode(array('validation_notice' => json_encode(strip_tags($form_errors)), 'page_url' => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])) : null,
			)
		);
		$post_info = \Drupal\Component\Serialization\Json::encode(
			array(
			'comment_type' => $spam_check['type'] . '_feedback',
			'post_url' => \Drupal::request()->server->get('HTTP_REFERER'),
			)
		);
		$ct_request = new CleantalkRequest();
		$ct_request->auth_key = $ct_authkey;
		$ct_request->agent = CLEANTALK_USER_AGENT;
		$ct_request->response_lang = 'en';
		$ct_request->js_on = $checkjs;
		$ct_request->sender_info = $sender_info;
		$ct_request->post_info = $post_info;
		$ct_request->sender_email = $spam_check['sender_email'];
		$ct_request->sender_nickname = $spam_check['sender_nickname'];
		$ct_request->sender_ip = CleantalkHelper::ip_get(array('real'), false);
		$ct_request->x_forwarded_for = CleantalkHelper::ip_get(array('x_forwarded_for'), false);
		$ct_request->x_real_ip       = CleantalkHelper::ip_get(array('x_real_ip'), false);
		$ct_request->submit_time = self::_cleantalk_get_submit_time();

		switch ($spam_check['type']) {

			case 'comment':

			case 'contact':

			case 'webform':

			case 'forum_topic':

			case 'custom_contact_form':

				$timelabels_key = 'mail_error_comment';
				if (is_array($spam_check['message_body'])) {

					$spam_check['message_body'] = isset($spam_check['message_body']['message']) ? $spam_check['message_body']['message'] : implode("\n\n", $spam_check['message_body']);

				}

				$ct_request->message = $spam_check['message_title'] . " \n\n" . strip_tags($spam_check['message_body']);
				$ct_result = $ct->isAllowMessage($ct_request);	   

			break;

			case 'register':

				$timelabels_key = 'mail_error_reg';
				$ct_request->tz = $spam_check['timezone'];
				$ct_result = $ct->isAllowUser($ct_request);

			break;

		}

		$cleantalk_executed = true;;
		$ret_val = array();
		$ret_val['ct_request_id'] = $ct_result->id;

		if ($ct->server_change) {

			self::_cleantalk_set_ws($ct->work_url, $ct->server_ttl, \Drupal::time()->getRequestTime());

		}

		// First check errstr flag.

		if (!empty($ct_result->errstr) || (!empty($ct_result->inactive) && $ct_result->inactive == 1)) {

			// Cleantalk error so we go default way (no action at all).

			$ret_val['errno'] = 1;

			if($checkjs == 0) {

				$ret_val['allow'] = 0;

			}

			// Just inform admin.

			$err_title = \Drupal::request()->server->get('SERVER_NAME') . ' - CleanTalk hook error';

			if (!empty($ct_result->errstr)) {

				$ret_val['errstr'] = self::_cleantalk_filter_response($ct_result->errstr);

			}

			else {

				$ret_val['errstr'] = self::_cleantalk_filter_response($ct_result->comment);

			}

			$send_flag = FALSE;

			$result = \Drupal::database()->select('cleantalk_timelabels', 'c')->fields('c', array('ct_value'))->condition('ct_key', $timelabels_key, '=')->execute();
			$results = $result->fetchCol(0);

			if (count($results) == 0) {

				$send_flag = TRUE;

			}

			elseif (\Drupal::time()->getRequestTime() - 900 > $result->fetchObject()->ct_value) {

				// 15 minutes.

				$send_flag = TRUE;

			}

			if ($send_flag) {

				\Drupal::database()->merge('cleantalk_timelabels')->key(array('ct_key' => $timelabels_key,))->fields(array('ct_value' => \Drupal::time()->getRequestTime(),))->execute();

				// @FIXME
				// // @FIXME
				// // This looks like another module's variable. You'll need to rewrite this call
				// // to ensure that it uses the correct configuration object.
				// $to = variable_get('site_mail', ini_get('sendmail_from'));

				if (!empty($to)) {

					drupal_mail("cleantalk", $timelabels_key, $to, language_default(), array('subject' => $err_title, 'body' => $ret_val['errstr'], 'headers' => array()), $to, TRUE);

				}

			}

			return $ret_val;

		}

		$ret_val['errno'] = 0;

		if ($ct_result->allow == 1) {

			// Not spammer.

			$ret_val['allow'] = 1;

			// Store request_id in globals to store it in DB later.

			self::_cleantalk_ct_result('set',$ret_val['allow'], $ct_result->id);

			// Don't store 'ct_result_comment', means good comment.
		}

		else {

			// Spammer.

			$ret_val['allow'] = 0;
			$ret_val['ct_result_comment'] = self::_cleantalk_filter_response($ct_result->comment);

			// Check stop_queue flag.

			if ($spam_check['type'] == 'comment') {

				// Spammer and stop_queue == 0 - to manual approvement.

				$ret_val['stop_queue'] = $ct_result->stop_queue;

				// Store request_id and comment in static to store them in DB later.

				self::_cleantalk_ct_result('set', $ct_result->id, $ret_val['allow'], $ret_val['ct_result_comment']);

			}

		}

		return $ret_val;

	}

	/**
	 * Cleantalk inner function - performs CleanTalk comment|errstr filtering.
	 */

	static public function _cleantalk_filter_response($ct_response) {

	  if (preg_match('//u', $ct_response)) {

	    $err_str = preg_replace('/\*\*\*/iu', '', $ct_response);

	  }

	  else {

	    $err_str = preg_replace('/\*\*\*/i', '', $ct_response);

	  }

	  return \Drupal\Component\Utility\Xss::filter($err_str, array('a'));

	}

	/**
	 * Cleantalk inner function - stores spam checking result.
	 */

	static public function _cleantalk_ct_result($cmd = 'get', $id = '', $allow = 1, $comment = '') {

	  static $request_id = '';
	  static $result_allow = 1;
	  static $result_comment = '';

	  if ($cmd == 'set') {

	    $request_id = $id;
	    $result_allow = $allow;
	    $result_comment = $comment;

	  }

	  else {

	    return array(
	      'ct_request_id' => $request_id,
	      'ct_result_allow' => $result_allow,
	      'ct_result_comment' => $result_comment,
	    );

	  }

	}

	/**
	 * Cleantalk inner function - gets working server.
	 */

	static public function _cleantalk_get_ws() {

	  return array(
	    'work_url' => \Drupal::state()->get('cleantalk_work_url'),
	    'server_url' => 'http://moderate.cleantalk.org',
	    'server_ttl' => \Drupal::state()->get('cleantalk_server_ttl'),
	    'server_changed' => \Drupal::state()->get('cleantalk_server_changed'),
	  );

	}

	/**
	 * Cleantalk inner function - sets working server.
	 */

	static public function _cleantalk_set_ws($work_url = 'http://moderate.cleantalk.org', $server_ttl = 0, $server_changed = 0) {

	  \Drupal::state()->set('cleantalk_work_url',$work_url);
	  \Drupal::state()->set('cleantalk_server_ttl',$server_ttl);
	  \Drupal::state()->set('cleantalk_server_changed',$server_changed);

	}
	
	/**
	 * Cleantalk inner function - check form handlers for save to prevent checking drafts/preview.
	 */

	static public function _cleantalk_check_form_submit_handlers($submitHandlers) {

		if ($submitHandlers && is_array($submitHandlers)) {

			foreach ($submitHandlers as $handler) {

				if ($handler === '::save') {

					return true;

				}

			}

		}

		return false;

	}

	/**
	 * Cleantalk inner function - perform remote call
	 */

	static public function _cleantalk_apbct_remote_call__perform() {

		$remote_calls_config = \Drupal::state()->get('cleantalk_remote_calls');
		$remote_action = $_GET['spbc_remote_call_action'];
		$auth_key = trim(\Drupal::config('cleantalk.settings')->get('cleantalk_authkey'));

		if (array_key_exists($remote_action, $remote_calls_config)) {
					
			if (time() - $remote_calls_config[$remote_action]['last_call'] > APBCT_REMOTE_CALL_SLEEP) {

				$remote_calls_config[$remote_action]['last_call'] = time();
				\Drupal::state()->set('cleantalk_remote_calls',$remote_calls_config);

				if (strtolower($_GET['spbc_remote_call_token']) == strtolower(md5($auth_key))) {

					// Close renew banner

					if ($_GET['spbc_remote_call_action'] == 'close_renew_banner') {

						\Drupal::state()->set('cleantalk_show_renew_banner', 0);
						die('OK');

					// SFW update

					}

					elseif ($_GET['spbc_remote_call_action'] == 'sfw_update') {

						$sfw = new CleantalkSFW();					
						$result = $sfw->sfw_update($auth_key);
						die(empty($result['error']) ? 'OK' : 'FAIL '.json_encode(array('error' => $result['error_string'])));

					// SFW send logs

					}

					elseif ($_GET['spbc_remote_call_action'] == 'sfw_send_logs') {

						$sfw = new CleantalkSFW();					
						$result = $sfw->send_logs($auth_key);
						die(empty($result['error']) ? 'OK' : 'FAIL '.json_encode(array('error' => $result['error_string'])));

					// Update plugin

					}

					elseif ($_GET['spbc_remote_call_action'] == 'update_plugin') {

						//add_action('wp', 'apbct_update', 1);

					}

					else {

						die('FAIL '.json_encode(array('error' => 'UNKNOWN_ACTION_2')));

					}

				}

				else {

					die('FAIL '.json_encode(array('error' => 'WRONG_TOKEN')));

				}

			}

			else {

				die('FAIL '.json_encode(array('error' => 'TOO_MANY_ATTEMPTS')));

			}

		}

		else {

			die('FAIL '.json_encode(array('error' => 'UNKNOWN_ACTION')));

		}

	}		
}