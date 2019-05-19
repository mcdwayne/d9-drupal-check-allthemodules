<?php

namespace Drupal\supermonitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SupermonitoringController.
 *
 * @package Drupal\supermonitoring\Controller
 */
class SupermonitoringController extends ControllerBase {

	/**
	 * Get Supermonitoring Token.
	 *
	 * @return string
	 *   Supermonitoring token.
	 */
	private function supermonitoring_get_supermonitoring_token() {
	  $config = $this->config('supermonitoring.settings');
	  return $config->get('supermonitoring.token');
	}

	/**
	 * Get Service Domain.
	 *
	 * @return string
	 *   Service domain.
	 */
	private function supermonitoring_get_service_domain() {
	  return t("https://www.supermonitoring.com/");
	}

	public function services() {
		return [
           '#type' => 'inline_template',
           '#title' => t('Your Checks'),
           '#template' => $this->services_template(),
        ];
	}

	public function settings() {
		return [
           '#type' => 'inline_template',
           '#title' => t('Your Account'),
           '#template' => $this->settings_template(),
        ];
	}

	public function contacts() {
		return [
           '#type' => 'inline_template',
           '#title' => t('Your Contacts'),
           '#template' => $this->contacts_template(),
        ];
	}

	private function services_template() {

		$service_domain = $this->supermonitoring_get_service_domain();
	    $token = $this->supermonitoring_get_supermonitoring_token();

	    if ($token == NULL || empty($token)) {	     
	      $url = Url::fromRoute('supermonitoring_settings.admin');
		  $link = \Drupal::l(t('Configuration > User Interface > Super Monitoring'), $url); 

		  $output = t("Go to the configuration and enter your token " . $link);
	    }
	    else {
	      $output = '<iframe id="frame" width="100%" frameborder="0" src="' . $service_domain . 'index.php?wp_token=' . $token . '&amp;cms=drupal"></iframe>';
	    }

	    $output .= $this->supermonitoring_get_iframe_height();
	    return $output;
	}

	private function settings_template() {

		$service_domain = $this->supermonitoring_get_service_domain();
	    $token = $this->supermonitoring_get_supermonitoring_token();

	    if ($token == NULL || empty($token)) {	     
	      $url = Url::fromRoute('supermonitoring_settings.admin');
		  $link = \Drupal::l(t('Configuration > User Interface > Super Monitoring'), $url); 

		  $output = t("Go to the configuration and enter your token " . $link);
	    }
	    else {
	      $output = '<iframe id="frame" width="100%" frameborder="0" src="' . $service_domain . 'index.php?wp_token=' . $token . '&amp;cms=drupal&amp;s=settings"></iframe>';
	    }

	    $output .= $this->supermonitoring_get_iframe_height();
	    return $output;
	}

	private function contacts_template() {

		$service_domain = $this->supermonitoring_get_service_domain();
	    $token = $this->supermonitoring_get_supermonitoring_token();

	    if ($token == NULL || empty($token)) {	     
	      $url = Url::fromRoute('supermonitoring_settings.admin');
		  $link = \Drupal::l(t('Configuration > User Interface > Super Monitoring'), $url); 

		  $output = t("Go to the configuration and enter your token " . $link);
	    }
	    else {
	      $output = '<iframe id="frame" width="100%" frameborder="0" src="' . $service_domain . 'index.php?wp_token=' . $token . '&amp;cms=drupal&amp;s=contacts"></iframe>';
	    }

	    $output .= $this->supermonitoring_get_iframe_height();
	    return $output;
	}

	/**
	 * Get JavaScript.
	 *
	 * @return string
	 *   HTML
	 */
	private function supermonitoring_get_iframe_height() {
	  $output = '<script type="text/javascript">
	                function resizeIframe() {
	                    var height = document.documentElement.clientHeight;
	                    height -= document.getElementById(\'frame\').offsetTop;

	                    // not sure how to get this dynamically
	                    height -= 20; /* whatever you set your body bottom margin/padding to be */

	                    document.getElementById(\'frame\').style.height = height + "px";
	                }

	                document.getElementById(\'frame\').onload = resizeIframe;
	                window.onresize = resizeIframe;
	            </script>';

	  return $output;
	}
}
