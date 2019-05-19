<?php
/**
 * Created by Vincenzo Gambino
 * Date: 28/11/2016
 * Time: 23:00
 */

namespace Drupal\ssp_idp;


class SSPIDP {

  /**
   * @return null|void
   */
  public function getConfig() {
    $config = NULL;

    // Get the simplesamlphp session.
    $basedir = \Drupal::config('ssp_idp.settings')->get('ssp_idp_samlfolder');

    // if we don't have a va
    if (!strlen($basedir)) {
      return;
    }
    require_once($basedir . '/lib/_autoload.php');

    $sspConfig = \SimpleSAML_Configuration::getInstance();

    if (!is_object($sspConfig)) {
      return;
    }

    // get the secretsalt
    $config['secretsalt'] = $sspConfig->getValue('secretsalt');


    // get the baseurlpath
    $config['baseurlpath'] = '/' . $sspConfig->getValue('baseurlpath');
    unset($sspConfig);

    $sspAuthsources = \SimpleSAML_Configuration::getConfig('authsources.php');
    

    // get the cookie_name
    $config['cookie_name'] = $sspAuthsources->getValue('cookie_name', 'drupal_ssp_idp');

    unset($sspAuthsources);

    // make sure every configuration setting is present
    foreach ($config as $val) {

      if (!strlen($val)) {
        return;
      }

    }
    return $config;
  }

  /**
   * @param $account
   */
  public function ssp_idp($account) {

    // Get the configuration information from SimpleSAMLphp
    $sspConfig = $this->getConfig();
   
    // If we don't have configuration, exit without doing anything
    if (!is_array($sspConfig)) {
      $message = 'Could not use authdrupal , could not get the SimpleSAMLphp configuration.';
      \Drupal::logger('ssp_idp')->notice($message);
      // The least we can do is write something to the watchdog so someone will know what's happening.
      //watchdog('drupalauth4ssp', 'Could not use drupalauth for %name, could not get the SimpleSAMLphp configuration.', array('%name' => $user->name));
      return;
    }

    // Store the authenticated user's uid in the cookie (create a validation hash to ensure nobody tampers with the uid)
    setcookie($sspConfig['cookie_name'], sha1($sspConfig['secretsalt'] . $account->id) . ':' . $account->id, 0, $sspConfig['baseurlpath']);
    // if the ReturnTo URL is present, send the user to the URL
    if (isset($_GET['ReturnTo']) && $_GET['ReturnTo']) {
      header('Location: ' . $_GET['ReturnTo']);
      die;
    }
  }
}