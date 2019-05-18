<?php

/**
 * @file
 * Contain \Drupal\acquia_flush_varnish\Controller\AcquiaFlushVarnishController.
 */

namespace Drupal\acquia_flush_varnish\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default controller for the acquia_flush_varnish module.
 */
class AcquiaFlushVarnishController extends ControllerBase {
  /**
   * Function to reload previous page.
   */
  public function loadPage() {
    $request = \Drupal::request();
    return $request->server->get('HTTP_REFERER');
  }

  /**
   * Function to get response.
   */
  public function getResponse($url, $method, $credentials) {
    $uname = $credentials[0];
    $pword = $credentials[1];
    $headers['Authorization'] = ('Basic ' . base64_encode($uname . ':' . $pword));
    try {
      $response = \Drupal::httpClient()->request($method, $url, array('headers' => $headers, 'http_errors' => FALSE));
    }
    catch (RequestException $e) {
    }
    catch (BadResponseException $e) {
    }
    catch (\Exception $e) {
    }
    $data = (string) $response->getBody();
    $data = json_decode($data);
    return $data;
  }

  /**
   * Function to clear acquia varnish cache.
   */
  public function acquiaFlushVarnishCache() {
    $uname = \Drupal::config('acquia_flush_varnish.settings')->get('acquia_flush_varnish_email');
    $pword = \Drupal::config('acquia_flush_varnish.settings')->get('acquia_flush_varnish_privatekey');
    $mydomainenv = $_ENV['AH_SITE_ENVIRONMENT'];
    $mydomain = $_ENV['HTTP_HOST'];
    $credentials = array($uname, $pword);
    $mysites = $this->getResponse("https://cloudapi.acquia.com/v1/sites.json", 'GET', $credentials);
    $realm = preg_grep('/' . $_ENV['AH_SITE_GROUP'] . '/', $mysites);
    if ($uname != NULL && $pword != NULL) {
      foreach ($realm as $realmvalue) {
        $url_delete = "https://cloudapi.acquia.com/v1/sites/" . $realmvalue . "/envs/" . $mydomainenv . "/domains/" . $mydomain . "/cache.json";
        $output = $this->getResponse($url_delete, 'DELETE', $credentials);
        if (isset($output->description)) {
          break;
        }
      }
      if (isset($output->description)) {
        drupal_set_message($output->description);
      }
      else {
        if (isset($output->message) && $output->message == 'Not authorized') {
          drupal_set_message($output->message, 'error');
        }
      }
      return new RedirectResponse($this->loadPage());
    }
    else {
      drupal_set_message(t("Check your acquia API credentials"), 'error');
      return new RedirectResponse($this->loadPage());
    }
  }

}
