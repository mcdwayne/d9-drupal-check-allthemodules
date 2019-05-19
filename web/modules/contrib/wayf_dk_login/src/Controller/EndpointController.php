<?php

namespace Drupal\wayf_dk_login\Controller;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\wayf_dk_login\WAYF\SPorto;
use Drupal\Core\Controller\ControllerBase;
use Drupal\wayf_dk_login\WAYF\SPortoException;
use SebastianBergmann\Exporter\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class EndpointController extends ControllerBase {

  /**
   * Handle login redirect from WAYF.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  function consume() {
    // Get SAML response from the POST data.
    $SAMLResponse = \Drupal::request()->request->get('SAMLResponse');

    // Load configuration.
    $config = \Drupal::config('wayf_dk_login.settings');
    $sportoConfig = array(
		  'idp_certificate' => $config->get('idp_certificate'),
		  'sso' => $config->get('idp_sso'),
		  'private_key' => $config->get('sp_key'),
		  'asc' => $config->get('sp_endpoint'),
		  'entityid' => $config->get('sp_entityid'),
		);

    // Get scopes based on allowed organizations.
    $scopes = array_filter(json_decode($config->get('sp_organizations_active')));

    try {
      // Send request.
      $sporto = new SPorto($sportoConfig);
      $result = $sporto->redirect($SAMLResponse, $scopes);
    }
    catch (SPortoException $exception) {
      drupal_set_message(t("Login failed. Please contact the site administrator or try again."), 'error');
      \Drupal::logger('wayf_dk_login')->error($exception->getMessage());
      return $this->redirect('<front>');
    }

    if ($config->get('development_log_auth_data')) {
      \Drupal::logger('wayf_dk_login')->debug('Authentication data: %data', array('%data' => var_export($result, TRUE)));
    }

    // User the selected login hook (hook_wayf_dk_login_create_user) to process
    // the login.
    $hooks = $config->get('user_create_modules');
    foreach ($hooks as $module) {
      \Drupal::moduleHandler()->invoke($module, 'wayf_dk_login_create_user', array($result['attributes']));
    }

    return (new RedirectResponse($config->get('login_redirect')))->send();
  }

  /**
   * Logout of WAYF.
   */
  public function logout() {
    // Load configuration.
    $config = \Drupal::config('wayf_dk_login.settings');

    // Set library configuration.
    $sportoConfig = array(
      'idp_certificate' => $config->get('idp_certificate'),
      'sso' => $config->get('idp_sso'),
      'slo' => $config->get('idp_slo'),
      'private_key' => $config->get('sp_key'),
      'asc' => $config->get('sp_endpoint'),
      'entityid' => $config->get('sp_entityid'),
    );

    try {
      // Build SAML message and logout.
      $sporto = new SPorto($sportoConfig);

      // Check if user is logged into WAYF.
      if ($sporto->isLoggedIn()) {
        // Give other an change to clean up.
        \Drupal::moduleHandler()->invokeAll('wayf_dk_login_pre_logout');

        // Store destination if set before redirect.
        $destination = \Drupal::destination()->get();
        if (!empty($destination) && $destination != \Drupal::url('wayf_dk_login.logout')) {
          \Drupal::service('session')->set('wafy_dk_login_destination', $destination);
        }

        // Send logout message.
        $sporto->logout();
      }
      else {
        // Check if the user is logged into the site. The WAYF logout redirect may
        // have stopped the logout process. So this will give Drupal a change to
        // complete the logout.
        if (\Drupal::currentUser()->isAuthenticated()) {
          return $this->redirect('user.logout');
        }

        // Get destination from session.
        $destination = \Drupal::service('session')->get('wafy_dk_login_destination');
        if (!empty($destination)) {
          // Clean up session.
          \Drupal::service('session')->remove('wafy_dk_login_destination');

          // Redirect.
          return (new RedirectResponse($destination))->send();
        }
      }
    }
    catch (SPortoException $exception) {
      drupal_set_message(t("Logout failed. Please contact the site administrator or try again."), 'error');
      \Drupal::logger('wayf_dk_login')->error($exception->getMessage());
    }

    return $this->redirect('<front>');
  }

  /**
   * Generate metadata response.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function metadata() {
    $response = new Response(wayf_dk_login__generate_metadata());
    $response->headers->set('Content-Type', 'text/xml');

    return $response;
  }
}
