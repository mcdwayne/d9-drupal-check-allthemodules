<?php

namespace Drupal\partnersite_profile\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;



/**
 * Base class for Access Link generator plugins.
 */
abstract class LinkGeneratorBase extends PluginBase implements LinkGeneratorInterface {

	/**
	 * {@inheritdoc}
	 */
	public function id(){
		return $this->pluginDefinition['id'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function label(){
		return $this->pluginDefinition['label'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function type(){
		return $this->pluginDefinition['type'];
	}

	/**
	 * {@inheritdoc}
	 */
	abstract public function accessLinkBuild($account, $expire,  $redirect);

	/**
	 * {@inheritdoc}
	 */
	abstract public function prepareHashKey($account, $timestamp, $maptable, $mapsecret);

	/**
	 * {@inheritdoc}
	 */
	public function prepareTimeStamps($expire){

		$timestamp = \Drupal::time()->getRequestTime();

		if(is_numeric($expire)){
      $provided_expire_setting = '+'. $expire . 'days';
			$expire_timestamp = strtotime($provided_expire_setting, $timestamp);


			if ($expire_timestamp < $timestamp) {
				$expire_timestamp = $expire_timestamp + $timestamp;
			}
		}
		else {
			$expire_timestamp = strtotime('+1 day'); // from configuration
		}

		return $expire_timestamp;
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepareRedirect( $redirect = NULL ){
		if (!$redirect) {
			$config = \Drupal::config('partnersite_profile.adminsettings');
			$redirect = $config->get('fallback_destination_default');
			$customs = explode("\r\n", $config->get('target_custom'));

			if (is_array($customs)) {
				foreach ($customs as $custom) {
					$custom_option = explode("|", $custom);
					if( is_array($custom_option) && ($redirect == $custom_option[0] ) )
					{
						$redirect = $custom_option[1];
					}
				}
			}
		}
		// If there is STILL no path or the path is 'current', use the current path.
		if (!$redirect || $redirect == "<front>") {
			$url = Url::fromRoute('<front>');
			$redirect = $url->getInternalPath();
		}
		// Prepare redirect.
		if (!empty($redirect)) {
			$pathValidator = \Drupal::service('path.validator');
			if( $pathValidator->isValid($redirect) ){
				if ( UrlHelper::isExternal($redirect) ) {
					// url_is_external() only detects that url is absolute.
					$path = parse_url($redirect);
					$base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
					if ($path['scheme'] . '://' . $path['host'] == $base_url) {
						// redirect path must be encoded to avoid treating it as a normal drupal path.
						$redirect = urlencode(str_replace($base_url . '/', '', $redirect));
					}
				}
			}
			else {
				$redirect = '';
			}
		}

		return $redirect;
	}


}
