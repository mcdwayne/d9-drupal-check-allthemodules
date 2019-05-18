<?php

/**
 * @file
 * Contains \Drupal\qyweixin\CorpBase.
 */

namespace Drupal\qyweixin;

use Drupal\file\FileInterface;
use Drupal\qyweixin\lib\WXBizMsgCrypt;

class CorpBase {

	/* Const for user gender */
	const USER_GENDER_MALE=1;
	const USER_GENDER_FEMALE=2;
	
	/* Const for user status */
	const USER_STATUS_ENABLED=1;
	const USER_STATUS_DISABLED=0;
	
	/* Const for user subscribing status */
	const USER_SUBSCRIBE_STATUS_ALL=0;
	const USER_SUBSCRIBE_STATUS_SUBSCRIBED=1;
	const USER_SUBSCRIBE_STATUS_FREEZED=2;
	const USER_SUBSCRIBE_STATUS_UNSUBSCRIBED=4;
	
	/* Const for top level department id */
	const TOP_LEVEL_DEPARTMENT_ID=1;
	
	/* Customer service stuff type */
	const CUSTOMER_SERVICE_TYPE_ALL=0;
	const CUSTOMER_SERVICE_TYPE_INTERNAL=1;
	const CUSTOMER_SERVICE_TYPE_EXTERNAL=2;

	/* Material types */
	const MATERIAL_TYPE_MPNEWS='mpnews';
	const MATERIAL_TYPE_IMAGE='image';
	const MATERIAL_TYPE_VOICE='voice';
	const MATERIAL_TYPE_VIDEO='video';
	const MATERIAL_TYPE_FILE='file';
	
	/**
	* The corpid of this corp.
	*
	* @var string
	*/
	protected static $corpid;

	/**
	* The corpsecret of this corp.
	*
	* @var string
	*/
	protected static $corpsecret;

	/**
	* The access_token of this qyweixin account.
	*
	* @var string
	*/
	protected static $access_token='';

	/**
	* The time access_token be expired.
	*
	* @var int
	*/
	protected static $access_token_expires_in=0;

	/**
	 * Generate private noncestr to be used in other functions
	 *
	 * @return string
	 *   The noncestr return generated
	 */
	private static function createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}

	/**
	 * Retrieve access_token to be used in other functions
	 *
	 * @return string
	 *   The access_token return by Tencent qyweixin interface
	 */
	public static function getAccessToken($secret='') {
		if(empty(self::$corpid)) self::$corpid=\Drupal::config('qyweixin.general')->get('corpid');
		if(empty(self::$corpsecret)) self::$corpsecret=\Drupal::config('qyweixin.general')->get('corpsecret');
		if(empty(self::$access_token)) self::$access_token=\Drupal::state()->get('qyweixin.access_token');
		if(empty(self::$access_token_expires_in)) self::$access_token_expires_in=\Drupal::state()->get('qyweixin.access_token.expires_in');
		
		if(empty($secret))
			$secret=self::$corpsecret;
		
		if(empty(self::$access_token) || empty(self::$access_token_expired_in) || self::$access_token_expires_in > time()-5 || $secret!==self::$corpsecret) {
			self::$corpid=\Drupal::config('qyweixin.general')->get('corpid');
			self::$corpsecret=\Drupal::config('qyweixin.general')->get('corpsecret');
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=%s&corpsecret=%s', self::$corpid, $secret);
			try {
				$data = (string) \Drupal::httpClient()->get($url)->getBody();
				$r=json_decode($data);
				if(empty($r))
					throw new \RuntimeException(json_last_error_msg(), json_last_error());
				if(!empty($r->errcode))
					throw new \InvalidArgumentException($r->errmsg, $response->errcode);
				if($secret==self::$corpsecret) {
					\Drupal::state()->set('qyweixin.access_token', $r->access_token);
					\Drupal::state()->set('qyweixin.access_token.expires_in', $r->expires_in+time());
					self::$access_token=$r->access_token;
					self::$access_token_expires_in=$r->expires_in+time();
				} else {
					return ['access_token'=>$r->access_token, 'access_token_expires_in'=>$r->expires_in+time()];
				}
			} catch (\Exception $e) {
				throw new \Exception($e->getMessage(), $e->getCode());
			}
		}
		return self::$access_token;
	}

	/**
	 * Retrieve jsapi_ticket to be used in html5 pages
	 *
	 * @return string
	 *   The jsapi_ticket return by Tencent qyweixin interface
	 */
	public static function getJsapiTicket() {
		try {
			$access_token=self::getAccessToken();
			$jsapi_ticket=\Drupal::state()->get('qyweixin.jsapi_ticket');
			$jsapi_ticket_expires_in=\Drupal::state()->get('qyweixin.jsapi_ticket.expires_in');
			if(empty($jsapi_ticket) || empty($jsapi_ticket_expires_in) || ($jsapi_ticket_expires_in > time() -5) ) {
				$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=%s', $access_token);
				$data = (string) \Drupal::httpClient()->get($url)->getBody();
				$response=json_decode($data);
				if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
				if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
				$jsapi_ticket=$response->ticket;
				\Drupal::state()->set('qyweixin.jsapi_ticket', $response->ticket);
				\Drupal::state()->set('qyweixin.jsapi_ticket.expires_in', $response->expires_in+time());
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $jsapi_ticket;
		}
	}
	
	/**
	 * Produce jsapi injection code to be inserted to html page
	 *
	 * @return array
	 *   The settings corresponding to wx.config function as suggested by qyweixin
	 */
	public static function getJsapiInjection($url='', $jsApiList=[]) {
		$timestamp=time();
		$noncestr=self::createNonceStr();
		$config=[
			'jsapi_ticket' => self::getJsapiTicket(),
			'noncestr' => $noncestr,
			'timestamp' => $timestamp,
			'url' => $url
		];
		$ret=[
			'corpId' => self::$corpid,
			'timestamp' => $timestamp,
			'nonceStr' => $noncestr,
			'signature' => sha1(implode('&',$config)),
			'jsApiList' => json_encode($jsApiList)
		];
		return $ret;
	}

	/**
	 * Wrapper of QyWeixin's getLoginInfo function.
	 *
	 * @param string $auth_code
	 *   The userid to set as auth succeeded.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function getLoginInfo($auth_code='') {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/service/get_login_info?access_token=%s', $access_token);
			$u=new \stdClass();
			$u->auth_code=$auth_code;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's user/authsucc function.
	 *
	 * @param string $userid
	 *   The userid to set as auth succeeded.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function userAuthSucc($userid) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/authsucc?access_token=%s&userid=%s', $access_token, $userid);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's user/create function.
	 *
	 * @param stdClass $user
	 *   The user to push to qyweixin's contact book, must complies user object specification in qyweixin.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function userCreate($user) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/create?access_token=%s', $access_token);
			$u=new \stdClass();
			$u->userid=$user->userid;
			\Drupal::moduleHandler()->alter('qyweixin_to_username', $u->userid);
			$u->name=$user->name;
			$u->email=$user->email;
			$u->department=$user->department;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's user/update function.
	 *
	 * @param stdClass $user
	 *   The user to push to qyweixin's contact book, must complies user object specification in qyweixin.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function userUpdate($user) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/update?access_token=%s', $access_token);
			$u=new \stdClass();
			$u->userid=$user->userid;
			\Drupal::moduleHandler()->alter('qyweixin_to_username', $u->userid);
			$u->name=$user->name;
			$u->email=$user->email;
			$u->department=$user->department;
			$u->enable=$user->enable;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's user/delete function.
	 *
	 * @param string or array of strings $userid
	 *   The user or users to push to qyweixin's contact book.
	 *   if $user is a array, then massive deletion(user/batchdeleted) might be called, and each
	 *   of the element should be a plain uid.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function userDelete($userid) {
		try {
			$access_token=self::getAccessToken();
			if(is_array($userid)) {
				$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/batchdelete?access_token=%s', $access_token);
				$u=new \stdClass();
				foreach($userid as $user) {
					\Drupal::moduleHandler()->alter('qyweixin_to_username', $user);
					$u->useridlist[]=(string)$user;
				}
				$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			} else {
				\Drupal::moduleHandler()->alter('qyweixin_to_username', $userid);
				$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/delete?access_token=%s&userid=%s', $access_token, $userid);
				$data = (string) \Drupal::httpClient()->get($url)->getBody();
			}
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's user/get function.
	 *
	 * @param string $userid
	 *   The userid to query from qyweixin's contact book.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 * @return stdClass
	 *   The user object retured by Tencent qyweixin interface.
	 */
	public static function userGet($userid) {
		try {
			$response=new \stdClass();
			$access_token=self::getAccessToken();
			\Drupal::moduleHandler()->alter('qyweixin_to_username', $userid);
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=%s&userid=%s', $access_token, $userid);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			\Drupal::moduleHandler()->alter('qyweixin_from_username', $response->userid);
			return $response;
		}
	}

	/**
	 * Wrapper of QyWeixin's user/simplelist function.
	 *
	 * @param int $departmentid
	 *   The id of department you want to fetch.
	 * @param boolean $fetch_child
	 *   1 means you want the members of sub departments should be fetched also.
	 * @param int $status
	 *   Following numbers could be used:
	 *   0: Allo
	 *   1 means you want the members of sub departments should be fetched also.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 * @return array of stdClass
	 *   The user objects retured by Tencent qyweixin interface.
	 */
	public static function userSimpleList($departmentid = 1, $fetch_child = FALSE, $status = USER_SUBSCRIBE_STATUS_UNSUBSCRIBED) {
		try {
			$userlist=[];
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token=%s&department_id=%s&fetch_child=%s&status=%s',
			$access_token, $departmentid, (int)$fetch_child, (int)$status);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);

			foreach($response->userlist as $user) {
				\Drupal::moduleHandler()->alter('qyweixin_from_username', $user->userid);
				$userlist[]=$user;
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $userlist;
		}
	}

	/**
	 * Wrapper of QyWeixin's user/list function.
	 *
	 * @param int $departmentid
	 *   The id of department you want to fetch.
	 * @param boolean $fetch_child
	 *   1 means you want the members of sub departments should be fetched also.
	 * @param int $status
	 *   Following numbers could be used:
	 *   0: Allo
	 *   1 means you want the members of sub departments should be fetched also.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 * @return array of stdClass
	 *   The user objects retured by Tencent qyweixin interface.
	 */
	public static function userList($departmentid = 1, $fetch_child = FALSE, $status = USER_SUBSCRIBE_STATUS_UNSUBSCRIBED) {
		try {
			$userlist=[];
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?access_token=%s&department_id=%s&fetch_child=%s&status=%s',
			$access_token, $departmentid, (int)$fetch_child, $status);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);

			foreach($response->userlist as $user) {
				\Drupal::moduleHandler()->alter('qyweixin_from_username', $user->userid);
				$userlist[]=$user;
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $userlist;
		}
	}

	/**
	 * Wrapper of QyWeixin's user/convert_to_openid function.
	 *
	 * @param string $userid
	 *   The userid of follower you want to convert.
	 * @param string $agentid
	 *   The agentid you want to convert.
	 *
	 * @return openid or stdClass
	 *   The openid or Object retured by Tencent qyweixin interface.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function userConvertToOpenid($userid, $agentid=NULL) {
		try {
			$openid='';
			$access_token=self::getAccessToken();
			\Drupal::moduleHandler()->alter('qyweixin_to_username', $userid);
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token=%s', $access_token);
			$u=new \stdClass();
			$u->userid=$userid;
			if(!empty($agentid)) $u->agentid=$agentid;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			if(empty($agentid)) $openid=$response->openid;
			else {
				$openid=$response;
				unset($openid->errcode);
				unset($openid->errmsg);
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $openid;
		}
	}

	/**
	 * Wrapper of QyWeixin's user/convert_to_userid function.
	 *
	 * @param string $openid
	 *   The openid of follower you want to convert.
	 * @param boolean $return_all
	 *   Some times the 3rd party app will alter userid from string to array. By default this interface will return only the 1st element
	 *     of the 3rd-party array. But if you want, the interface could return all the array.
	 *
	 * @return userid
	 *   The userid retured by Tencent qyweixin interface.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function userConvertToUserid($openid, $return_all=FALSE) {
		try {
			$userid='';
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_userid?access_token=%s', $access_token);
			$u=new \stdClass();
			$u->openid=$openid;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$userid=$response->userid;
			\Drupal::moduleHandler()->alter('qyweixin_from_username', $userid);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			if($return_all || !is_array($userid)) return $userid;
			else return $userid[0];
		}
	}

	/**
	 * Wrapper of QyWeixin's department/create function.
	 *
	 * @param stdClass $department
	 *   The department object you want to push qyweixin's database.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function departmentCreate($department) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/department/create?access_token=%s', $access_token);
			$d=new \stdClass();
			$d->id=(int)$department->id;
			$d->name=$department->name;
			$d->order=$department->order;
			$d->parentid=$department->parentid;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($d, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's department/update function.
	 *
	 * @param stdClass $department
	 *   The department object you want to push qyweixin's database.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function departmentUpdate($department) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/department/update?access_token=%s', $access_token);
			$d=new \stdClass();
			$d->id=(int)$department->id;
			$d->name=$department->name;
			$d->order=$department->order;
			$d->parentid=$department->parentid;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($d, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's department/delete function.
	 *
	 * @param stdClass $department
	 *   The department object you want to push qyweixin's database.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function departmentDelete($departmentid) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/department/delete?access_token=%s&id=%s', $access_token, (int)$departmentid);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Wrapper of QyWeixin's tag/create function.
	 *
	 * @param stdClass $tag
	 *   The tag to push to qyweixin's contact book, must complies tag object specification in qyweixin.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function tagCreate($tag) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/create?access_token=%s', $access_token);
			$t=new \stdClass();
			$t->tag=$tag->tagid;
			$t->tagname=$tag->tagname;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($t, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's tag/update function.
	 *
	 * @param stdClass $tag
	 *   The tag to update to qyweixin's contact book, must complies tag object specification in qyweixin.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function tagUpdate($tag) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/update?access_token=%s', $access_token);
			$t=new \stdClass();
			$t->tag=$tag->tagid;
			$t->tagname=$tag->tagname;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($t, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's tag/delete function.
	 *
	 * @param string $tagid
	 *   The tag to delete from qyweixin's contact book.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function tagDelete($tag) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/delete?access_token=%s&tagid=%s', $access_token, $tag);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's tag/get function.
	 *
	 * @param string $tagid
	 *   The tag to retreive from qyweixin's contact book.
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function tagGet($tag) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/get?access_token=%s&tagid=%s', $access_token, $tag);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);

			foreach($response->userlist as &$user) {
				\Drupal::moduleHandler()->alter('qyweixin_from_username', $user->userid);
				$userlist[]=$user;
			}
			
			unset($response->errcode);
			unset($response->errmsg);
			return $response;
			
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's tag/addtagusers function.
	 *
	 * @param string $tagid
	 *   The tag to add to qyweixin's contact book.
	 * @param array $list
	 *   Keyed array, $list['userlist'=>[], 'partylist'=[]].
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function tagAddTagUsers($tag, $list=[]) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/addtagusers?access_token=%s', $access_token);
			
			$u=new \stdClass();
			$u->tagid=$tag;
			if(isset($list['userlist'])) {
				$u->userlist=[];
				foreach($list['userlist'] as $user) {
					\Drupal::moduleHandler()->alter('qyweixin_to_username', $user);
					$u->useridlist[]=(string)$user;
				}
			}
			if(isset($list['partylist'])) {
				$u->partylist=$list['partylist'];
			}

			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);

			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's tag/deltagusers function.
	 *
	 * @param string $tagid
	 *   The tag to delete from qyweixin's contact book.
	 * @param array $list
	 *   Keyed array, $list['userlist'=>[], 'partylist'=[]].
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function tagDelTagUsers($tag, $list=[]) {
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/deltagusers?access_token=%s', $access_token);
			
			$u=new \stdClass();
			$u->tagid=$tag;
			if(isset($list['userlist'])) {
				$u->userlist=[];
				foreach($list['userlist'] as $user) {
					\Drupal::moduleHandler()->alter('qyweixin_to_username', $user);
					$u->useridlist[]=(string)$user;
				}
			}
			if(isset($list['partylist'])) {
				$u->partylist=$list['partylist'];
			}

			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($u, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);

			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * Wrapper of QyWeixin's tag/list function.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 * @return array of stdClass
	 *   The taglist objects retured by Tencent qyweixin interface.
	 */
	public static function tagList() {
		$ret=[];
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/tag/list?access_token=%s', $access_token);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$ret=$response->taglist;
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $ret;
		}
	}
	
	/**
	 * Wrapper of QyWeixin's agent/list function.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 * @return array of stdClass
	 *   The agentlist objects retured by Tencent qyweixin interface.
	 */
	public static function agentList() {
		$ret=[];
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/agent/list?access_token=%s', $access_token);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$ret=$response->agentlist;
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $ret;
		}
	}
	
	public static function verifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $token, $encodingAesKey) {
		if(empty(self::$corpid)) self::$corpid=\Drupal::config('qyweixin.general')->get('corpid');
		$wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, self::$corpid);
		$sEchoStr='';
		$errCode = $wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
		if($errCode) 
			throw new \Exception('VerifyURL error', $errCode);
		else return $sEchoStr;
	}
	
	public static function decryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $token, $encodingAesKey) {
		if(empty(self::$corpid)) self::$corpid=\Drupal::config('qyweixin.general')->get('corpid');
		$sMsg='';
		$wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, self::$corpid);
		$errCode = $wxcpt->DecryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);
		unset($wxcpt);
		if($errCode) 
			throw new \Exception('Decrypt error', $errCode);
		else return $sMsg;
	}
	
	public static function encryptMsg($sRespData, $sReqTimeStamp, $sReqNonce, $token, $encodingAesKey) {
		if(empty(self::$corpid)) self::$corpid=\Drupal::config('qyweixin.general')->get('corpid');
		$sEncryptMsg='';
		$wxcpt = new WXBizMsgCrypt($token, $encodingAesKey, self::$corpid);
		$errCode = $wxcpt->EncryptMsg($sRespData, $sReqTimeStamp, $sReqNonce, $sEncryptMsg);
		unset($wxcpt);
		if($errCode) 
			throw new \Exception('Encrypt error', $errCode);
		else return $sEncryptMsg;
	}

	/**
	 * Wrapper of QyWeixin's media/upload function.
	 *
	 * @param string $type
	 *   The type of file you want to upload.
	 * @param FileInterface $file
	 *   The file you want to upload.
	 *
	 * @return media_id
	 *   The media_id returned by Tencent qyweixin interface.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function mediaUpload(FileInterface $file, $type='file') {
		$media_id='';
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/media/upload?access_token=%s&type=%s', $access_token, $type);
			$handle=fopen($file->getFileUri(), 'r');
			$body=[[
				'name' => 'media',
				'filename' => $file->getFilename(),
				'filelength' => $file->getSize(),
				'content-type' => $file->getMimeType(),
				'contents' => $handle,
			]];
			$data = (string) \Drupal::httpClient()->post($url, ['multipart'=>$body])->getBody();
			fclose($handle);
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$media_id=$response->media_id;
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $media_id;
		}
	}
	
	/**
	 * Wrapper of QyWeixin's media/get function.
	 *
	 * @param string $media_id
	 *   The media_id you uploaded.
	 * @param $destination, $managed, $replace
	 *   See documentation of system_retrieve_file().
	 *
	 * @return instance of \Drupal\file\FileInterface or FALSE on error
	 *   The FileInterface object of the file received, or FALSE on error.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function mediaGet($media_id, $destination = NULL, $managed = FALSE, $replace = FILE_EXISTS_RENAME) {
		$file=FALSE;
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/media/get?access_token=%s&media_id=%s', $access_token, $media_id);
			$response=\Drupal::httpClient()->get($url);
			$data=(string) $response->getBody();

			$json=json_decode($data);
			if(!empty($json->errcode)) throw new \Exception($json->errmsg, $json->errcode);

			$parsed_url = parse_url($url);
			if (!isset($destination)) {
				$header=explode('"',$response->getHeader('Content-disposition'));
				$path = file_build_uri(\Drupal\Core\File\FileSystem::basename($header[1]));
			}
			else {
				if (is_dir(drupal_realpath($destination))) {
					// Prevent URIs with triple slashes when glueing parts together.
					$path = str_replace('///', '//', "$destination/") . drupal_basename($parsed_url['path']);
				}
				else {
					$path = $destination;
				}
			}
			$file = $managed ? file_save_data($data, $path, $replace) : file_unmanaged_save_data($data, $path, $replace);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $file;
		}
	}

	/**
	 * Wrapper of QyWeixin's shakearound/getshakeinfo function.
	 *
	 * @param string $ticket
	 *   The ticket generate by shakearound feature.
	 *
	 * @return stdClass
	 *   The object returned by Tencent qyweixin interface.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function getShakeInfo($ticket) {
		$ret=new \stdClass();
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/shakearound/getshakeinfo?access_token=%s', $access_token);
			$t=new \stdClass();
			$t->ticket=$ticket;
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($t, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$ret=$response->data;
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $ret;
		}
	}
	
	/**
	 * Wrapper of QyWeixin's kf/List function.
	 *
	 * @param enum $type
	 *   The type of customer service stuff required by shakearound feature.
	 *
	 * @return stdClass
	 *   The object returned by Tencent qyweixin interface.
	 *
	 *   Exception could be thrown if error occurs. The caller should take care of the exception.
	 *
	 */
	public static function kfList($type=CUSTOMER_SERVICE_TYPE_ALL) {
		$ret=new \stdClass();
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/kf/list?access_token=%s&type=%s', $access_token, $type);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$ret=$response;
			unset($ret->errcode);
			unset($ret->errmsg);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $ret;
		}
	}

}
