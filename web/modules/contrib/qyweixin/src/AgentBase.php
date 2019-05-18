<?php

/**
 * @file
 * Contains \Drupal\qyweixn\AgentBase.
 */

namespace Drupal\qyweixin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\qyweixin\CorpBase;
use Drupal\qyweixin\MessageInterface;

/**
 * Provides a base class for QiyeWeixin Agent.
 *
 * @see plugin_api
 */
abstract class AgentBase extends PluginBase implements AgentInterface {

	/**
	 * The qyweixin agent ID.
	 *
	 * @var string
	 */
	protected $agentId;
	protected $secret;
	protected $pluginId;
	protected $token;
	protected $encodingAesKey;
	
	/**
	* The access_token of this qyweixin agent.
	*
	* @var string
	*/
	protected $access_token='';

	/**
	* The time access_token be expired.
	*
	* @var int
	*/
	protected $access_token_expires_in=0;
	
	/**
	* {@inheritdoc}
	*/
	public function getPluginId() {
		return $this->pluginId;
	}

	public function getConfiguration() {
		return array(
			'id' => $this->getPluginId(),
			'agentId' => $this->agentId,
			'secret' => $this->secret,
			'token' => $this->token,
			'encodingAesKey' => $this->encodingAesKey,
			'data' => $this->configuration
		);
	}
	
	/**
	* {@inheritdoc}
	*/
	public function __construct(array $configuration, $plugin_id, $plugin_definition) {
		$configuration+=['agentId'=>\Drupal::config('qyweixin.general')->get('plugin.'.$plugin_id.'.agentid')];
		$configuration+=array(
			'secret'=>\Drupal::config('qyweixin.general')->get('agent.'.$configuration['agentId'].'.secret'),
			'token'=>\Drupal::config('qyweixin.general')->get('agent.'.$configuration['agentId'].'.token'),
			'encodingAesKey'=>\Drupal::config('qyweixin.general')->get('agent.'.$configuration['agentId'].'.encodingaeskey')
		);
		$secret=\Drupal::config('qyweixin.general')->get('agent.'.$configuration['agentId'].'.secret');
		if(empty($secret))
			$configuration+=['secret'=>\Drupal::config('qyweixin.general')->get('corpsecret')];
		else
			$configuration+=['secret'=>$secret];
		parent::__construct($configuration, $plugin_id, $plugin_definition);
		$configuration['data']=$configuration;
		$this->setConfiguration($configuration);
	}

	/**
	* {@inheritdoc}
	*/
	public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
		return new static(
			$configuration,
			$plugin_id,
			$plugin_definition
		);
	}

	/**
	* {@inheritdoc}
	*/
	public function setConfiguration(array $configuration) {
		$configuration += array(
			'data' => array(),
			'agentId' => '',
			'token' => '',
			'encodingAesKey' => '',
		);
		
		$this->configuration = $configuration['data'] + $this->defaultConfiguration();
		$this->agentId = $configuration['agentId'];
		$this->secret = $configuration['secret'];
		$this->token = $configuration['token'];
		$this->encodingAesKey = $configuration['encodingAesKey'];
		return $this;
	}

	/**
	* {@inheritdoc}
	*/
	public function defaultConfiguration() {
		return array();
	}

	/**
	* {@inheritdoc}
	*/
	public function calculateDependencies() {
		return array();
	}
	
	protected function getAccessToken() {
		if(empty($this->agentId)) return FALSE;
		
		if(empty($this->secret)) return CorpBase::getAccessToken($this->secret);

		if(empty($this->access_token)) $this->access_token=\Drupal::state()->get('qyweixin.'.$this->agentId.'.access_token');
		if(empty($this->access_token_expires_in)) $this->access_token_expires_in=\Drupal::state()->get('qyweixin.'.$this->agentId.'.access_token.expires_in');

		if(empty($this->access_token) || empty($this->access_token_expired_in) || $this->access_token_expires_in > time()-5) {
			$ret=CorpBase::getAccessToken($this->secret);
			\Drupal::state()->set('qyweixin.'.$this->agentId.'.access_token', $ret['access_token']);
			\Drupal::state()->set('qyweixin.'.$this->agentId.'.access_token.expires_in', $ret['access_token_expires_in']);
			$this->access_token=$ret['access_token'];
			$this->access_token_expires_in=$ret['access_token_expires_in'];
		}
		return $this->access_token;
	}
	
	/**
	 * Retreive agent settings from qyweixin server
	 *
	 * @return stdClass or FALSE
	 *   The object returned by Tencent server or FALSE if error occured.
	 */
	public function agentGet() {
		if(empty($this->agentId)) return FALSE;
		$ret=FALSE;
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/agent/get?access_token=%s&agentid=%s', $access_token, $this->agentId);
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
	
	/**
	 * Retreive agent settings from qyweixin server
	 *
	 * @param stdClass agent
	 *    This agent object as what qyweixin requires, except that the agentid which will filled automatically.
	 *
	 * @return this
	 */
	public function agentSet($agent) {
		if(empty($this->agentId)) return FALSE;
		try {
			$access_token=self::getAccessToken();
			$agent->agentid=$this->agentId;
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/agent/set?access_token=%s', $access_token);
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($agent, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $this;
		}
	}
	
	/**
	 * Send private message to specific user
	 *
	 * @param stdClass/MessageBase message
	 *   body as what qyweixin requires, except that the agentid which will be filled automatically.
	 * @param Boolean issafe
	 *   Whether the message is safe
	 * @return this
	 */
	public function messageSend($message, $issafe=FALSE) {
		if(empty($message) || !is_object($message)) return $this;
		if($message instanceof MessageInterface) {
			$body=new \stdClass();
			if(is_array($message->getToUser())) {
				$users=[];
				foreach($message->getToUser() as $u) {
					\Drupal::moduleHandler()->alter('qyweixin_to_username', $u);
					$users[]=$u;
				}
				$touser=implode('|',$users);
			}
			else {
				$touser=$message->getToUser();
				\Drupal::moduleHandler()->alter('qyweixin_to_username', $touser);
			}
			$body->touser=$touser;
			$body->msgtype=$message->getMsgType();
			$body->safe=intval($issafe);
			try {
				switch($body->msgtype) {
					case MessageBase::MESSAGE_TYPE_TEXT:
						$body->text=new \stdClass();
						$body->text->content=$message->getContent();
						break;
					case MessageBase::MESSAGE_TYPE_IMAGE:
						$body->image=new \stdClass();
						$body->image->media_id=CorpBase::mediaUpload($message->getFile(), 'image');
						break;
					case MessageBase::MESSAGE_TYPE_VOICE:
						$body->voice=new \stdClass();
						$body->voice->media_id=CorpBase::mediaUpload($message->getFile(), 'voice');
						break;
					case MessageBase::MESSAGE_TYPE_VIDEO:
						$body->video=new \stdClass();
						$body->video->media_id=CorpBase::mediaUpload($message->getFile(), 'video');
						$body->video->title=$message->getTitle();
						$body->video->description=$message->getDescription();
						break;
					case MessageBase::MESSAGE_TYPE_FILE:
						$body->file=new \stdClass();
						$body->file->media_id=CorpBase::mediaUpload($message->getFile(), 'file');
						break;
				}
			} catch (\Exception $e) {
				throw new \Exception($e->getMessage(), $e->getCode());
			}
		} else {
			$body=$message;
			$body->safe=intval($issafe);
		}

		try {
			$access_token=self::getAccessToken();
			$body->agentid=$this->agentId;
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=%s', $access_token);
			$data = (string) \Drupal::httpClient()->post($url, ['body'=>json_encode($body, JSON_UNESCAPED_UNICODE)])->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			if(!empty($response->invaliduser)) {
				throw new \Exception(sprintf('Invalid user: %s', $response->invaliduser), 40031);
			}
		} catch (\Exception $e) {
			\Drupal::logger('qyweixin')
				->error('Message send to %user failed: %errcode: %errmsg',
					['%user'=>$message->getToUser(), '%errcode'=>$e->getCode(), '%errmsg'=>$e->getMessage()]
				);
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $this;
		}
	}
	
	/**
	 * Retreive menu used in this agent
	 *
	 * @return this
	 */
	public function menuGet() {
		if(empty($this->agentId)) return FALSE;
		$ret = new \stdClass();
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/menu/get?access_token=%s&agentid=%s', $access_token, $this->agentId);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response = json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
			$ret=$response;
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $ret;
		}
	}

	/**
	 * Delete menu used in this agent
	 *
	 * @return this
	 */
	public function menuDelete() {
		if(empty($this->agentId)) return FALSE;
		try {
			$access_token=self::getAccessToken();
			$url=sprintf('https://qyapi.weixin.qq.com/cgi-bin/menu/del?access_token=%s&agentid=%s', $access_token, $this->agentId);
			$data = (string) \Drupal::httpClient()->get($url)->getBody();
			$response=json_decode($data);
			if(empty($response)) throw new \RuntimeException(json_last_error_msg(), json_last_error());
			if($response->errcode) throw new \InvalidArgumentException($response->errmsg, $response->errcode);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode());
		} finally {
			return $this;
		}
	}
	
  	abstract public function defaultResponse($message);

}
