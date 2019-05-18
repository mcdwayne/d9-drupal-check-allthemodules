<?php

namespace Drupal\qyweixin\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\qyweixin\CorpBase;
use Drupal\qyweixin\MessageBase;

/**
 * Defines the QyWeixin Drupal mail backend, using QyWeixin sendMessage function.
 *
 * @Mail(
 *   id = "qyweixin_mail",
 *   label = @Translation("QyWeixin mailer"),
 *   description = @Translation("Sends the message as plain text, using QyWeixin's sendMessage function.")
 * )
 */
class QyWeixinMail implements MailInterface {

	/**
	* Concatenates and wraps the email body for plain-text mails.
	*
	* @param array $message
	*   A message array, as described in hook_mail_alter().
	*
	* @return array
	*   The formatted $message.
	*/
	public function format(array $message) {
		// Join the body array into one string.
		$message['body'] = implode("\n\n", $message['body']);

		// Convert any HTML to plain-text.
		$message['body'] = MailFormatHelper::htmlToText($message['body']);
		// Wrap the mail body for sending.
		$message['body'] = MailFormatHelper::wrapMail($message['body']);

		return $message;
	}

	/**
	* Sends an email message.
	*
	* @param array $message
	*   A message array, as described in hook_mail_alter().
	*
	* @return bool
	*   TRUE if the mail was successfully accepted, otherwise FALSE.
	*
	* @see http://php.net/manual/function.mail.php
	* @see \Drupal\Core\Mail\MailManagerInterface::mail()
	*/
	public function mail(array $message) {
		if(filter_var($message['to'], FILTER_VALIDATE_EMAIL)) {
			$message['to']=user_load_by_mail($message['to']);
		}
		if($message['to'] instanceOf \Drupal\user\UserInterface) {
			$message['to']=$user->getUsername();
		}
		if(is_string($message['to'])) {
			try {
				$app=\Drupal::service('plugin.manager.qyweixin.agent')->createInstance(\Drupal::config('qyweixin.general.mailer.appid'));
				$msg=new MessageBase();
				$msg->setMsgType(MessageBase::MESSAGE_TYPE_TEXT)->setContent($message['body'])->setToUser($message['to']);
				$app->messageSend($msg);
				return TRUE;
			} catch (\Exception $e) {
				return FALSE;
			}
		}
		return FALSE;
}
