<?php

/**
 * @file
 * Contains \Drupal\qyweixn\MessageBase.
 */

namespace Drupal\qyweixin;
use Drupal\qyweixin\MessageInterface;

/**
 * Provides a message base class for QiyeWeixin Agent.
 *
 */
class MessageBase implements MessageInterface {
	protected $toUser;
	protected $msgType;
	protected $content;
	protected $file;
	protected $permanent;
	protected $title;
	protected $description;
	
	public function setMsgType($type=MESSAGE_TYPE_TEXT) {
		$this->msgType=$type;
		return $this;
	}
	
	public function setContent($content='') {
		$this->content=\Drupal\Component\Utility\Html::decodeEntities((string)$content);
		return $this;
	}
	
	public function setFile(FileInterface $file, $permanent=FALSE) {
		$this->file=$file;
		$this->permanent=$permanent;
		return $this;
	}
	
	public function setTitle($title='') {
		$this->title=$title;
		return $this;
	}

	public function setDescription($description='') {
		$this->description=$description;
		return $this;
	}

	public function setToUser($user, $isOpenID=FALSE) {
		if(is_array($user)) $this->toUser=implode('|',$user);
		else if($isOpenID) $this->toUser=\Drupal\qyweixin\CorpBase::userConvertToUserid($user);
		else $this->toUser=$user;
		return $this;
	}
	
	public function getMsgType() {
		return $this->msgType;
	}
	
	public function getContent() {
		return $this->content;
	}
	
	public function getToUser() {
		return $this->toUser;
	}
	
	public function getFile() {
		return $this->file;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function isFilePermanent() {
		return $this->permanent;
	}
}
