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
 * Class CustomAccessLinkGenerator
 * @package Drupal\partnersite_profile\Plugin\LinkGenerator
 *
 * @LinkGenerator(
 *   id = "custom_linkgenerator",
 *   label = @Translation("Custom Link Generation"),
 *   type = "custom"
 * )
 *
 */
class CustomAccessLinkGenerator extends LinkGeneratorBase
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
		$partner_profiles = \Drupal::service('entity_type.manager')->getStorage('partnersite_profiles')->load($account->get('name')->value);
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

		$hash = $this->prepareHashKey(
			account,
			$timestamp,
			$partner_profiles->getAuthMappingHash(),
			$partner_profiles->getAuthSecret()
		);

		$url = Url::fromRoute(
			"partnersite_profile.custom_validate_and_grant",
			array('uid_encoded' => $id, 'timestamp' => $timestamp),
			array(
				'query' => array(
					'destination' => $redirect,
					'apikey' => $hash,
					'auth_div' => $partner_profiles->getAuthDiv(),
					),
				'absolute' => TRUE,
				'language' => $account->language(),
			)
		)->toString();
		return $url;

	}

	/**
	 * @param UserInterface $account
	 * @param integer $timestamp
	 * @param string|null $maptable
	 * @param string|null $mapsecret
	 * @return string
	 */
	public function prepareHashKey($account, $timestamp, $maptable = NULL, $mapsecret = NULL )
	{
		$timestamp_mapped_done = strtr($timestamp, '0123456789', $maptable);
		$hash_generated = md5($mapsecret.$timestamp_mapped_done).$timestamp_mapped_done;
		/****/
		 //$logger_factory = \Drupal::service('logger.factory');

		/******/
		return  $hash_generated;
	}
}