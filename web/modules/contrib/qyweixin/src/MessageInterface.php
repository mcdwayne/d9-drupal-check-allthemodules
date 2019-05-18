<?php

/**
 * @file
 * Contains \Drupal\qyweixn\MessageBase.
 */

namespace Drupal\qyweixin;
use Drupal\qyweixin\AgentInterface;

/**
 * Provides a message interface for Message used in QiyeWeixin Agent.
 *
 */
interface MessageInterface {

	const MESSAGE_TYPE_TEXT='text';
	const MESSAGE_TYPE_MPNEWS='mpnews';
	const MESSAGE_TYPE_IMAGE='image';
	const MESSAGE_TYPE_VOICE='voice';
	const MESSAGE_TYPE_VIDEO='video';
	const MESSAGE_TYPE_FILE='file';
	
	public function setMsgType($type=MESSAGE_TYPE_TEXT);
	
	public function setContent($content='');
	
	public function setFile(FileInterface $file, $permanent=FALSE);
	
	public function setTitle($title='');

	public function setDescription($description='');

	public function setToUser($user, $isOpenID=FALSE);
	
	public function getMsgType();
	
	public function getContent();
	
	public function getToUser();
	
	public function getFile();
	
	public function getTitle();
	
	public function getDescription();
	
	public function isFilePermanent();
}
