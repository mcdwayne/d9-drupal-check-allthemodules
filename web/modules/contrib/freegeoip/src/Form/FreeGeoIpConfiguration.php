<?php

/**
 * @file
 * Contains \Drupal\freegeoip\Form\FreeGeoIpConfiguration.
 */

namespace Drupal\freegeoip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FreeGeoIpConfiguration extends ConfigFormBase {

  public function getFormId() {
    return 'freegeoip_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

  	$config = $this->config('freegeoip.settings');

    $form['description'] = array(
	    '#type' => 'item',
	    '#title' => t('Settings form to set the server location of freegeoip.net Server.'),
	  );
	  $form['freegeoip_url'] = array(
	    '#type' => 'textfield',
	    '#title' => t('GeoIp URL'),
	    '#description' => t('Enter the http://geoip.nekudo.com server URL, format : http://{I.P}:{port}. Default is http://geoip.nekudo.com'),
	    '#default_value' => $config->get('freegeoip_url'),
	    '#required' => TRUE,
	  );
	  $form['freegeoip_default_country'] = array(
	    '#type' => 'textfield',
	    '#title' => t('Default Country'),
	    '#description' => t('Enter the default country'),
	    '#default_value' => $config->get('freegeoip_default_country'),
	    '#required' => TRUE,
	  );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  	$userInputValues = $form_state->getUserInput();
  	$user_ip = freegeoip_get_user_ip();

	  $test = freegeoip_get_geoip_data($userInputValues['freegeoip_url'] . '/api/' . $user_ip);

	  if (is_array($test)) {
	    drupal_set_message("Connection to " . $userInputValues['freegeoip_url'] . ' successful.');
	  }
	  else {
	    $form_state->setErrorByName('geoip_url', 'Error connecting to ' . $userInputValues['freegeoip_url'] . '. Please check if it is a valid freegeoip.net server.');
	  }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  	$config = $this->config('freegeoip.settings');
    $userInputValues = $form_state->getUserInput();

    $config->set('freegeoip_url', $userInputValues['freegeoip_url']);
    $config->set('freegeoip_default_country', $userInputValues['freegeoip_default_country']);
    $config->save();
    parent::submitForm($form, $form_state);
  }
}