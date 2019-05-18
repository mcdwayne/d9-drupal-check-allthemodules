<?php

namespace Drupal\partnersite_profile\Plugin\LinkGenerator;


use Drupal\Core\Annotation\Translation;
use Drupal\Core\Url;
use Drupal\partnersite_profile\Annotation\LinkGenerator;
use Drupal\partnersite_profile\Plugin\LinkGeneratorBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;


/**
 *
 * Provide access link in native format
 *
 * Class NativeAccessLinkGenerator
 * @package Drupal\partnersite_profile\Plugin\LinkGenerator
 *
 * @LinkGenerator(
 *   id = "native_linkgenerator",
 *   label = @Translation("Native Link Generation"),
 *   type = "native"
 * )
 *
 */
class NativeAccessLinkGenerator extends LinkGeneratorBase
{

	/**
	 * Generates Access Link.
	 *
	 * @param object $account
	 *   User account. Should be activated. If not specified current user account
	 *   will be used.
	 * @param string $expire
	 *   Expiration time. Value could be a number of seconds from now
	 *   Default is '+1 day'.
	 * @param string $redirect
	 *   Path to redirect user after success login, and it should  be an existing internal path.
	 *
	 * @return string
	 *   Returns an absolute access URL.
	 */

	public function accessLinkBuild($account, $expire, $redirect)
	{
		$config = \Drupal::config('partnersite_profile.adminsettings');
		$timestamp = $this->prepareTimeStamps($expire);
		$redirect = $this->prepareRedirect($redirect);

		// Use current user account by default.
		if (empty($account)) {
			$account = User::load(\Drupal::currentUser()->id());
		}

		if( !$account->isActive() )
		{
			\Drupal::logger('Link Generator')->debug("The link could not be generted for blocked users!");
			return false;
		}

		$id = base64_encode($account->get('name')->value);
		$hash = $this->prepareHashKey($account, $timestamp, NULL, NULL );

		$url = Url::fromRoute(
			"partnersite_profile.native_validate_and_grant",
			array('uid_encoded' => $id, 'timestamp' => $timestamp, 'hashed_pass' => $hash),
			array(
				'query' => array('destination' => $redirect),
				'absolute' => TRUE,
				'language' => $account->language(),
			)
		)->toString();
		return $url;

	}

	/**
	 * @param UserInterface $account
	 * @param integer $timestamp
	 * @param null $maptable
	 * @param null $mapsecret
	 * @return string
	 */
	public function prepareHashKey($account, $timestamp, $maptable = NULL, $mapsecret = NULL )
	{
		return  user_pass_rehash($account, $timestamp);
	}
}